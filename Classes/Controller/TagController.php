<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\TagController as BaseTagController;
use Psr\Http\Message\ResponseInterface;
use Remind\HeadlessNews\Service\JsonService;

/**
 * @property \TYPO3Fluid\Fluid\View\AbstractTemplateView $view
 */
class TagController extends BaseTagController
{
    private ?JsonService $jsonService = null;

    public function injectNewsJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    /**
     * @param mixed[] $overwriteDemand
     */
    public function listAction(array $overwriteDemand = null): ResponseInterface
    {
        parent::listAction($overwriteDemand);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        /** @var mixed[] $pageData */
        $pageData = $variables['pageData'];
        $listPid = $this->settings['listPid'] ? ((int) $this->settings['listPid']) : $pageData['uid'];

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\GeorgRinger\News\Domain\Model\Tag> $tagsQueryResult */
        $tagsQueryResult = $variables['tags'];

        $overwriteDemandTags = $overwriteDemand ? (int)($overwriteDemand['tags'] ?? false) : false;

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
            ->uriFor(null, null, 'News');

        $result = [
            'settings' => [
                'templateLayout' => $this->settings['templateLayout'] ?? null,
            ],
            'tags' => [
                'all' => [
                    'active' => !$overwriteDemandTags,
                    'link' => $uri,
                ],
                'list' => [],
            ],
        ];

        foreach ($tagsQueryResult->toArray() as $tag) {
            /** @var \GeorgRinger\News\Domain\Model\Tag $tag */

            $uri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid($listPid)
                ->uriFor(null, ['overwriteDemand' => ['tags' => $tag->getUid()]], 'News');

            $tagJson = $this->jsonService?->serializeTag($tag);
            $tagJson['link'] = $uri;
            $tagJson['active'] = $overwriteDemandTags === $tag->getUid();

            $result['tags']['list'][] = $tagJson;
        }

        return $this->jsonResponse(json_encode($result) ?: null);
    }
}
