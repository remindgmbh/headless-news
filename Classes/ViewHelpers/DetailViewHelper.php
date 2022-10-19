<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\ViewHelpers;

use Closure;
use Remind\HeadlessNews\Utility\JsonUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class DetailViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    private const ARGUMENT_NEWS = 'news';
    private const ARGUMENT_SETTINGS = 'settings';

    public function initializeArguments()
    {
        $this->registerArgument(self::ARGUMENT_NEWS, 'object', 'news', true);
        $this->registerArgument(self::ARGUMENT_SETTINGS, 'array', 'settings', true);
    }

    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        /** @var \GeorgRinger\News\Domain\Model\News $news */
        $news = $arguments[self::ARGUMENT_NEWS];

        $settings = $arguments[self::ARGUMENT_SETTINGS];

        $jsonUtility = GeneralUtility::makeInstance(JsonUtility::class, $renderingContext, $renderChildrenClosure, $settings);

        $viewHelperInvoker = $renderingContext->getViewHelperInvoker();

        $result = [
            'news' => $jsonUtility->processDetailNews($news),
            'contentElements' => array_map(function ($contentElementId) use ($viewHelperInvoker, $renderingContext) {
                return json_decode($viewHelperInvoker->invoke(
                    CObjectViewHelper::class,
                    [
                        'data' => $contentElementId,
                        'typoscriptObjectPath' => 'lib.tx_news.contentElementRendering',
                    ],
                    $renderingContext
                ));
            }, explode(',', $news->getTranslatedContentElementIdList())),
            'settings' => [
                'templateLayout' => $settings['templateLayout'],
                'action' => 'detail',
            ],
        ];

        return $result;
    }
}
