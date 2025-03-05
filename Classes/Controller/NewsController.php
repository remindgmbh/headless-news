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

/**
 * @property \TYPO3Fluid\Fluid\View\AbstractTemplateView $view
 */
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
     * @param mixed[]|null $overwriteDemand
     */
    public function listAction(array $overwriteDemand = null): ResponseInterface
    {
        parent::listAction($overwriteDemand);

        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var \TYPO3\CMS\Core\Pagination\PaginatorInterface $paginator  */
        $paginator = $variables['pagination']['paginator'];

        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<News> $newsQueryResult */
        $newsQueryResult = $paginator->getPaginatedItems();

        $currentPage = $variables['pagination']['currentPage'];

        /** @var \TYPO3\CMS\Core\Pagination\PaginationInterface $pagination */
        $pagination = $variables['pagination']['pagination'];

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult<\GeorgRinger\News\Domain\Model\Category> | null $selectedCategories */
        $selectedCategories = $variables['categories'];

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) ($this->settings['listPid'] ?? null))
            ->build();

        $result = [
            'news' => array_map(function (News $news) {
                return $this->newsJsonService?->serializeListNews($news);
            }, $newsQueryResult->toArray()),
            'pagination' => $this->jsonService?->serializePagination(
                $this->uriBuilder,
                $pagination,
                'currentPage',
                $currentPage
            ),
            'settings' => [
                'categories' => $this->newsJsonService?->serializeCategories($selectedCategories?->toArray() ?? []),
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        $event = $this->eventDispatcher->dispatch(new NewsListActionEvent($result, $this->settings));
        $result = $event->getValues();

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    public function selectedListAction(): ResponseInterface
    {
        parent::selectedListAction();
        $variables = $this->view->getRenderingContext()->getVariableProvider()->getAll();

        /** @var News[] $news */
        $news = $variables['news'];

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) ($this->settings['listPid'] ?? null))
            ->build();

        $result = [
            'news' => array_map(function (News $news) {
                return $this->newsJsonService?->serializeListNews($news);
            }, iterator_to_array($news)),
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    /**
     * Missing news parameter type hint leads to exception in ActionController
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     *
     * @param News $news
     * @param int $currentPage // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.UselessAnnotation
     */
    public function detailAction(?News $news = null, $currentPage = 1): ResponseInterface
    {
        parent::detailAction($news, $currentPage);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        /** @var News $news */
        $news = $variables['newsItem'];

        $listLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int) ($this->settings['backPid'] ?? null))
            ->build();

        $result = [
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
            'news' => $this->newsJsonService?->serializeDetailNews($news),
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
        ];

        $this->newsBreadcrumbTitleProvider?->setTitle($news->getTitle());

        return $this->jsonResponse(json_encode($result) ?: null);
    }

    /**
     * @param mixed[] $overwriteDemand
     */
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
            ->setTargetPageUid((int) ($this->settings['listPid'] ?? null))
            ->build();

        $allYearsUri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($listPid)
            ->uriFor();

        $result = [
            'settings' => [
                'listLink' => $listLink,
                'templateLayout' => $this->settings['templateLayout'],
            ],
            'years' => [
                'all' => [
                    'active' => !$overwriteDemandYear,
                    'count' => 0,
                    'link' => $allYearsUri,
                ],
                'list' => [],
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
                'active' => $overwriteDemandYear === $yearTitle && !$overwriteDemandMonth,
                'count' => $count,
                'link' => $yearUri,
                'months' => [],
                'title' => $yearTitle,
            ];

            foreach ($months as $month => $count) {
                $monthTitle = strval($month);

                $monthUri = $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid($listPid)
                    ->uriFor(null, ['overwriteDemand' => ['year' => $yearTitle, 'month' => $monthTitle]]);

                $year['months'][] = [
                    'active' => $overwriteDemandYear === $yearTitle && $overwriteDemandMonth === $monthTitle,
                    'count' => $count,
                    'link' => $monthUri,
                    'title' => $monthTitle,
                ];
            }

            $result['years']['list'][] = $year;
        }
        return $this->jsonResponse(json_encode($result) ?: null);
    }
}
