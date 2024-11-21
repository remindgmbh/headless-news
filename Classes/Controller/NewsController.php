<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\NewsController as BaseNewsController;
use GeorgRinger\News\Domain\Model\News;
use Psr\Http\Message\ResponseInterface;
use Remind\Headless\Service\JsonService;
use Remind\HeadlessNews\BreadcrumbTitle\NewsBreadcrumbTitleProvider;
use Remind\HeadlessNews\Event\NewsListActionEvent;
use Remind\HeadlessNews\Service\JsonService as NewsJsonService;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;

class NewsController extends BaseNewsController
{
    private ?JsonService $jsonService = null;
    private ?NewsJsonService $newsJsonService = null;
    private ?NewsBreadcrumbTitleProvider $newsBreadcrumbTitleProvider = null;

    public function injectJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    public function injectNewsJsonService(NewsJsonService $newsJsonService): void
    {
        $this->newsJsonService = $newsJsonService;
    }

    public function injectNewsBreadcrumbTitleProvider(
        NewsBreadcrumbTitleProvider $newsBreadcrumbTitleProvider
    ): void {
        $this->newsBreadcrumbTitleProvider = $newsBreadcrumbTitleProvider;
    }

    /**
     * Output a list view of news
     *
     * @param array|null $overwriteDemand
     *
     * @return ResponseInterface
     */
    public function listAction(array $overwriteDemand = null): ResponseInterface
    {
        parent::listAction($overwriteDemand);
        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var \TYPO3\CMS\Core\Pagination\PaginatorInterface $paginator  */
        $paginator = $variables['pagination']['paginator'];

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $newsQueryResult */
        $newsQueryResult = $paginator->getPaginatedItems();

        $currentPage = $variables['pagination']['currentPage'];

        /** @var \TYPO3\CMS\Core\Pagination\PaginationInterface $pagination */
        $pagination = $variables['pagination']['pagination'];

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $selectedCategories */
        $selectedCategories = $variables['categories'];

        $categories = [];
        if ($selectedCategories) {
            $categories = $this->newsJsonService->serializeCategories($selectedCategories->toArray());
        }

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) $this->settings['listPid'] ?? null)
            ->build();

        $result = [
            'pagination' => $this->jsonService->serializePagination($pagination, 'currentPage', $currentPage),
            'news' => array_map(function (News $news) {
                return $this->newsJsonService->serializeListNews($news);
            }, $newsQueryResult->toArray()),
            'settings' => [
                'categories' => $categories,
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        $event = $this->eventDispatcher->dispatch(new NewsListActionEvent($result, $this->settings));
        $result = $event->getValues();

        return $this->jsonResponse(json_encode($result));
    }

    public function selectedListAction(): ResponseInterface
    {
        parent::selectedListAction();
        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var iterable $news */
        $news = $variables['news'];

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) $this->settings['listPid'] ?? null)
            ->build();

        $result = [
            'news' => array_map(function (News $news) {
                return $this->newsJsonService->serializeListNews($news);
            }, iterator_to_array($news)),
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * Single view of a news record
     *
     * @param \GeorgRinger\News\Domain\Model\News $news news item
     * @param int $currentPage current page for optional pagination
     *
     * @return ResponseInterface
     */
    public function detailAction(News $news = null, $currentPage = 1): ResponseInterface
    {
        parent::detailAction($news, $currentPage);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        /** @var News $news */
        $news = $variables['newsItem'];

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) $this->settings['backPid'] ?? null)
            ->build();

        $result = [
            'news' => $this->newsJsonService->serializeDetailNews($news),
            'contentElements' => array_map(function ($contentElementId) use ($renderingContext) {
                return json_decode($renderingContext->getViewHelperInvoker()->invoke(
                    CObjectViewHelper::class,
                    [
                        'data' => $contentElementId,
                        'typoscriptObjectPath' => 'lib.tx_news.contentElementRendering',
                    ],
                    $renderingContext
                ));
            }, explode(',', $news->getTranslatedContentElementIdList())),
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        $this->newsBreadcrumbTitleProvider->setTitle($news->getTitle());

        return $this->jsonResponse(json_encode($result));
    }

    public function dateMenuAction(?array $overwriteDemand = null): ResponseInterface
    {
        parent::dateMenuAction($overwriteDemand);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        $data = $variables['data'];
        $listPid = $variables['listPid'];

        $overwriteDemandYear = $overwriteDemand ? (int)($overwriteDemand['year'] ?? false) : false;
        $overwriteDemandMonth = $overwriteDemand ? ($overwriteDemand['month'] ?? false) : false;

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) $this->settings['listPid'] ?? null)
            ->build();

        $allYearsUri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
            ->uriFor();

        $result = [
            'years' => [
                'all' => [
                    'link' => $allYearsUri,
                    'active' => !$overwriteDemandYear,
                    'count' => 0,
                ],
                'list' => [],
            ],
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        foreach ($data['single'] as $yearTitle => $months) {
            $yearUri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid($listPid)
                ->uriFor(null, ['overwriteDemand' => ['year' => $yearTitle]]);

            $count = $data['total'][$yearTitle];

            $result['years']['all']['count'] += $count;

            $year = [
                'title' => $yearTitle,
                'link' => $yearUri,
                'active' => $overwriteDemandYear === $yearTitle && !$overwriteDemandMonth,
                'count' => $count,
                'months' => [],
            ];

            foreach ($months as $month => $count) {
                $monthTitle = strval($month);

                $monthUri = $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid($listPid)
                    ->uriFor(null, ['overwriteDemand' => ['year' => $yearTitle, 'month' => $monthTitle]]);

                $year['months'][] = [
                    'title' => $monthTitle,
                    'link' => $monthUri,
                    'active' => $overwriteDemandYear === $yearTitle && $overwriteDemandMonth === $monthTitle,
                    'count' => $count,
                ];
            }

            $result['years']['list'][] = $year;
        }
        return $this->jsonResponse(json_encode($result));
    }
}
