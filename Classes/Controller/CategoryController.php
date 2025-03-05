<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\CategoryController as BaseCategoryController;
use Psr\Http\Message\ResponseInterface;
use Remind\HeadlessNews\Service\JsonService;

/**
 * @property \TYPO3Fluid\Fluid\View\AbstractTemplateView $view
 */
class CategoryController extends BaseCategoryController
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

        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var mixed[] $categories */
        $categories = $variables['categories'];

        /** @var mixed[] $pageData */
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
                    'active' => !$overwriteDemandCategories,
                    'link' => $uri,
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

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    /**
     * @param mixed[] $category
     * @return mixed[]
     */
    protected function processCategory(array $category, int $listPid, mixed $overwriteDemandCategories): array
    {
        /** @var \GeorgRinger\News\Domain\Model\Category $item */
        $item = $category['item'];

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
            ->uriFor(null, ['overwriteDemand' => ['categories' => $item->getUid()]], 'News');

        $categoryJson = $this->jsonService?->serializeCategory($item);
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
