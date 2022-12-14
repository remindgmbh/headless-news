<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\ViewHelpers;

use Closure;
use RuntimeException;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class TagsListViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    private const ARGUMENT_TAGS = 'tags';
    private const ARGUMENT_SETTINGS = 'settings';
    private const ARGUMENT_OVERWRITE_DEMAND = 'overwriteDemand';

    public function initializeArguments()
    {
        $this->registerArgument(self::ARGUMENT_TAGS, 'array', 'tags', true);
        $this->registerArgument(self::ARGUMENT_SETTINGS, 'array', 'settings', true);
        $this->registerArgument(self::ARGUMENT_OVERWRITE_DEMAND, 'array', 'overwriteDemand', true);
    }

    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (!$renderingContext instanceof RenderingContext) {
            throw new RuntimeException(
                sprintf(
                    'RenderingContext must be instance of "%s", but is instance of "%s"',
                    RenderingContext::class,
                    get_class($renderingContext)
                ),
                1663759243
            );
        }

        $tags = $arguments[self::ARGUMENT_TAGS];
        $settings = $arguments[self::ARGUMENT_SETTINGS];
        $overwriteDemand = $arguments[self::ARGUMENT_OVERWRITE_DEMAND];

        $overwriteDemandTags = $overwriteDemand ? (int)($overwriteDemand['tags'] ?? false) : false;

        $uriBuilder = $renderingContext->getUriBuilder();

        $uri = $uriBuilder
            ->reset()
            ->setTargetPageUid((int)$settings['listPid'])
            ->uriFor();

        $result = [
            'tags' => [
                'all' => [
                    'active' => !$overwriteDemandTags,
                    'slug' => $uri,
                ],
                'list' => [],
            ],
            'settings' => [
                'orderBy' => $settings['orderBy'],
                'orderDirection' => $settings['orderDirection'],
                'templateLayout' => $settings['templateLayout'],
                'action' => 'tagsList',
            ],
        ];

        foreach ($tags as $tag) {
            /** @var \GeorgRinger\News\Domain\Model\Tag $tag */

            $uri = $uriBuilder
                ->reset()
                ->setTargetPageUid((int)$settings['listPid'])
                ->uriFor(null, ['overwriteDemand' => ['tags' => $tag->getUid()]]);

            $result['tags']['list'][] = [
                'uid' => $tag->getUid(),
                'pid' => $tag->getPid(),
                'title' => $tag->getTitle(),
                'slug' => $uri,
                'active' => $overwriteDemandTags === $tag->getUid(),
                'seo' => [
                    'title' => $tag->getSeoTitle(),
                    'description' => $tag->getSeoDescription(),
                    'headline' => $tag->getSeoHeadline(),
                    'text' => $tag->getSeoText(),
                ],
            ];
        }

        return $result;
    }
}
