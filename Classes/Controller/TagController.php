<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\TagController as BaseTagController;
use Psr\Http\Message\ResponseInterface;
use Remind\HeadlessNews\Service\JsonService;

class TagController extends BaseTagController
{
    private ?JsonService $jsonService = null;

    public function injectNewsJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    /**
     * List tags
     *
     * @param array $overwriteDemand
     *
     * @return ResponseInterface
     */
    public function listAction(array $overwriteDemand = null): ResponseInterface
    {
        parent::listAction($overwriteDemand);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $tagsQueryResult */
        $tagsQueryResult = $variables['tags'];

        $overwriteDemandTags = $overwriteDemand ? (int)($overwriteDemand['tags'] ?? false) : false;

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$this->settings['listPid'] ?? null)
            ->uriFor();

        $result = [
            'tags' => [
                'all' => [
                    'active' => !$overwriteDemandTags,
                    'link' => $uri,
                ],
                'list' => [],
            ],
            'settings' => [
                'orderBy' => $this->settings['orderBy'] ?? null,
                'orderDirection' => $this->settings['orderDirection'] ?? null,
                'templateLayout' => $this->settings['templateLayout'] ?? null,
                'action' => 'tagsList',
            ],
        ];

        foreach ($tagsQueryResult->toArray() as $tag) {
            /** @var \GeorgRinger\News\Domain\Model\Tag $tag */

            $uri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid((int)$this->settings['listPid'])
                ->uriFor(null, ['overwriteDemand' => ['tags' => $tag->getUid()]]);

            $tagJson = $this->jsonService->serializeTag($tag);
            $tagJson['link'] = $uri;
            $tagJson['active'] = $overwriteDemandTags === $tag->getUid();

            $result['tags']['list'][] = $tagJson;
        }

        return $this->jsonResponse(json_encode($result));
    }
}
