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

        /** @var array $pageData */
        $pageData = $variables['pageData'];

        $overwriteDemandCategories = $overwriteDemand ? (int)($overwriteDemand['categories'] ?? false) : false;

        $listPid = $this->settings['listPid'] ? ((int) $this->settings['listPid']) : $pageData['uid'];

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
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
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        foreach ($categories as $category) {
            $result['categories']['list'][] = $this->processCategory(
                $category,
                $listPid,
                $overwriteDemandCategories
            );
        }

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * Process category
     *
     * @param array $category
     * @param int $listPid
     * @param mixed $overwriteDemandCategories
     *
     * @return array
     */
    protected function processCategory(array $category, int $listPid, mixed $overwriteDemandCategories): array
    {
        /** @var \GeorgRinger\News\Domain\Model\Category $item */
        $item = $category['item'];

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
            ->uriFor(null, ['overwriteDemand' => ['categories' => $item->getUid()]], 'News');

        $categoryJson = $this->jsonService->serializeCategory($item);
        $categoryJson['link'] = $uri;
        $categoryJson['active'] = $overwriteDemandCategories === $item->getUid();

        if (isset($category['children'])) {
            $categoryJson['children'] = [];
            foreach ($category['children'] as $childCategory) {
                $categoryJson['children'][] = $this->processCategory(
                    $childCategory,
                    $listPid,
                    $overwriteDemandCategories
                );
            }
        }

        return $categoryJson;
    }
}
