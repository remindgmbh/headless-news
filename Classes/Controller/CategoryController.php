<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\CategoryController as BaseCategoryController;
use Psr\Http\Message\ResponseInterface;
use Remind\HeadlessNews\Service\JsonService;

class CategoryController extends BaseCategoryController
{
    private ?JsonService $jsonService = null;

    public function injectNewsJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    /**
     * List categories
     *
     * @param array $overwriteDemand
     *
     * @return ResponseInterface
     */
    public function listAction(array $overwriteDemand = null): ResponseInterface
    {
        parent::listAction($overwriteDemand);

        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var array $categories */
        $categories = $variables['categories'];

        $overwriteDemandCategories = $overwriteDemand ? (int)($overwriteDemand['categories'] ?? false) : false;

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$this->settings['listPid'])
            ->uriFor(null, null, 'News');

        $result = [
            'categories' => [
                'all' => [
                    'link' => $uri,
                    'active' => !$overwriteDemandCategories,
                ],
                'list' => [],
            ],
            'settings' => [
                'orderBy' => $this->settings['orderBy'],
                'orderDirection' => $this->settings['orderDirection'],
                'templateLayout' => $this->settings['templateLayout'],
                'action' => 'categoryMenu',
            ],
        ];

        foreach ($categories as $category) {

            /** @var \GeorgRinger\News\Domain\Model\Category $item */
            $item = $category['item'];

            $uri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid((int)$this->settings['listPid'])
                ->uriFor(null, ['overwriteDemand' => ['categories' => $item->getUid()]], 'News');

            $categoryJson = $this->jsonService->serializeCategory($item);
            $categoryJson['link'] = $uri;
            $categoryJson['active'] = $overwriteDemandCategories === $item->getUid();

            $result['categories']['list'][] = $categoryJson;
        }

        return $this->jsonResponse(json_encode($result));
    }
}