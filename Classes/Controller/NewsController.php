<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Controller;

use GeorgRinger\News\Controller\NewsController as BaseNewsController;
use GeorgRinger\News\Domain\Model\News;
use Psr\Http\Message\ResponseInterface;
use Remind\Headless\Service\JsonService;
use Remind\HeadlessNews\Service\JsonService as NewsJsonService;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;

class NewsController extends BaseNewsController
{
    private ?JsonService $jsonService = null;
    private ?NewsJsonService $newsJsonService = null;

    public function injectJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    public function injectNewsJsonService(NewsJsonService $newsJsonService): void
    {
        $this->newsJsonService = $newsJsonService;
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
        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $newsQueryResult */
        $newsQueryResult = $variables['news'];

        $currentPage = $variables['pagination']['currentPage'];
        /** @var \TYPO3\CMS\Core\Pagination\PaginationInterface $pagination */
        $pagination = $variables['pagination']['pagination'];

        $result = [
            'pagination' => $this->jsonService->serializePagination($pagination, 'currentPage', $currentPage),
            'news' => array_map(function (News $news) {
                return $this->newsJsonService->serializeListNews($news);
            }, $newsQueryResult->toArray()),
            'settings' => [
                'orderBy' => $this->settings['orderBy'],
                'orderDirection' => $this->settings['orderDirection'],
                'templateLayout' => $this->settings['templateLayout'],
                'action' => 'list',
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
                'templateLayout' => $this->settings['templateLayout'],
                'action' => 'detail',
            ],
        ];

        return $this->jsonResponse(json_encode($result));
    }

    /**
     * TODO: remove function and rename headlessDateMenuAction to dateMenuAction
     *       once base function returns ResponseInterface
     *
     * @param array|null $overwriteDemand
     *
     * @return void
     */
    public function dateMenuAction(array $overwriteDemand = null): void
    {
        $this->forward('headlessDateMenu', null, null, ['overwriteDemand' => $overwriteDemand]);
    }

    public function headlessDateMenuAction(?array $overwriteDemand = null): ResponseInterface
    {
        parent::dateMenuAction($overwriteDemand);
        $renderingContext = $this->view->getRenderingContext();
        $variables = $renderingContext->getVariableProvider()->getAll();

        $data = $variables['data'];

        $overwriteDemandYear = $overwriteDemand ? (int)($overwriteDemand['year'] ?? false) : false;
        $overwriteDemandMonth = $overwriteDemand ? ($overwriteDemand['month'] ?? false) : false;

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$this->settings['listPid'])
            ->uriFor();

        $result = [
            'years' => [
                'all' => [
                    'link' => $uri,
                    'active' => !$overwriteDemandYear,
                    'count' => 0,
                ],
                'list' => [],
            ],
            'settings' => [
                'orderBy' => $this->settings['orderBy'],
                'orderDirection' => $this->settings['orderDirection'],
                'templateLayout' => $this->settings['templateLayout'],
                'action' => 'dateMenu',
            ],
        ];

        foreach ($data['single'] as $yearTitle => $months) {
            $yearUri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid((int)$this->settings['listPid'])
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
                    ->setTargetPageUid((int)$this->settings['listPid'])
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
