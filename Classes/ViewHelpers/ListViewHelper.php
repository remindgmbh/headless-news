<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\ViewHelpers;

use Closure;
use GeorgRinger\News\Domain\Model\News;
use Remind\HeadlessNews\Utility\JsonUtility;
use Remind\Typo3Headless\ViewHelpers\PaginationViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ListViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    private const ARGUMENT_NEWS = 'news';
    private const ARGUMENT_SETTINGS = 'settings';
    private const ARGUMENT_PAGINATION = 'pagination';

    public function initializeArguments()
    {
        $this->registerArgument(self::ARGUMENT_NEWS, 'object', 'news', true);
        $this->registerArgument(self::ARGUMENT_SETTINGS, 'array', 'settings', true);
        $this->registerArgument(self::ARGUMENT_PAGINATION, 'array', 'pagination', true);
    }

    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        /** @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $news */
        $news = $arguments[self::ARGUMENT_NEWS];

        $settings = $arguments[self::ARGUMENT_SETTINGS];

        /** @var array $pagination */
        $pagination = $arguments[self::ARGUMENT_PAGINATION];

        $jsonUtility = GeneralUtility::makeInstance(JsonUtility::class, $renderingContext, $renderChildrenClosure, $settings);

        $viewHelperInvoker = $renderingContext->getViewHelperInvoker();

        $result = [
            'pagination' => $viewHelperInvoker->invoke(
                PaginationViewHelper::class,
                [
                    PaginationViewHelper::ARGUMENT_PAGINATION => $pagination['pagination'],
                    PaginationViewHelper::ARGUMENT_CURRENT_PAGE => $pagination['currentPage'],
                    PaginationViewHelper::ARGUMENT_QUERY_PARAM => 'currentPage',
                ],
                $renderingContext
            ),
            'news' => array_map(function (News $news) use ($jsonUtility) {
                return $jsonUtility->processListNews($news);
            }, $news->toArray()),
            'settings' => [
                'orderBy' => $settings['orderBy'],
                'orderDirection' => $settings['orderDirection'],
                'templateLayout' => $settings['templateLayout'],
                'action' => 'list',
            ],
        ];

        return $result;
    }
}
