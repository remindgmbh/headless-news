<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Service;

use DateTime;
use FriendsOfTYPO3\Headless\Json\JsonDecoder;
use FriendsOfTYPO3\Headless\Utility\FileUtility;
use GeorgRinger\News\Domain\Model\Category;
use GeorgRinger\News\Domain\Model\FileReference;
use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Model\Tag;
use GeorgRinger\News\Service\SettingsService;
use GeorgRinger\News\ViewHelpers\LinkViewHelper;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;

class JsonService
{
    private ViewHelperInvoker $viewHelperInvoker;
    private array $settings;

    public function __construct(
        private readonly RenderingContext $renderingContext,
        private readonly FileUtility $fileUtility,
        private readonly JsonDecoder $jsonDecoder,
        private readonly ContentObjectRenderer $contentObjectRenderer,
        SettingsService $settingsService
    ) {
        $this->viewHelperInvoker = $renderingContext->getViewHelperInvoker();
        $this->settings = $settingsService->getSettings();
    }

    public function serializeListNews(News $news): array
    {
        $result = $this->serializeNews($news);
        $result['media'] = $this->serializeFiles($news->getMediaPreviews());
        $result['link'] = $this->viewHelperInvoker->invoke(
            LinkViewHelper::class,
            ['newsItem' => $news, 'settings' => $this->settings, 'uriOnly' => true],
            $this->renderingContext,
            function () {
            }
        );
        return $result;
    }

    public function serializeDetailNews(News $news): array
    {
        $result = $this->serializeNews($news);
        $result['media'] = $this->serializeFiles($news->getMediaNonPreviews());

        $this->contentObjectRenderer->start([]);
        $result['bodytext'] = $this->contentObjectRenderer->parseFunc(
            $news->getBodytext(),
            [],
            '< lib.parseFunc_links'
        );
        return $result;
    }

    public function serializeCategory(Category $category): array
    {
        return [
            'id' => $category->getUid(),
            'pid' => $category->getPid(),
            'title' => $category->getTitle(),
            'description' => $category->getDescription(),
            'slug' => $category->getSlug(),
            'seo' => [
                'title' => $category->getSeoTitle(),
                'description' => $category->getSeoDescription(),
                'headline' => $category->getSeoHeadline(),
                'text' => $category->getSeoText(),
            ],
        ];
    }

    public function serializeTag(Tag $tag): array
    {
        return [
            'id' => $tag->getUid(),
            'pid' => $tag->getPid(),
            'title' => $tag->getTitle(),
            'slug' => $tag->getSlug(),
            'seo' => [
                'title' => $tag->getSeoTitle(),
                'description' => $tag->getSeoDescription(),
                'headline' => $tag->getSeoHeadline(),
                'text' => $tag->getSeoText(),
            ],
        ];
    }

    private function serializeNews(News $news): array
    {
        return [
            'uid' => $news->getUid(),
            'title' => $news->getTitle(),
            'teaser' => $news->getTeaser(),
            'isTopNews' => $news->getIstopnews(),
            'crDate' => $this->serializeDate($news->getCrdate()),
            'tstamp' => $this->serializeDate($news->getTstamp()),
            'datetime' => $this->serializeDate($news->getDatetime()),
            'archive' => $this->serializeDateTime($news->getArchive()),
            'author' => [
                'name' => $news->getAuthor(),
                'email' => $news->getAuthorEmail(),
            ],
            'relatedFiles' => $news->getRelatedFiles()->count(),
            'categories' => $this->serializeCategories($news->getCategories()->toArray()),
            'tags' => $this->serializeTags($news->getTags()->toArray()),
            'metaData' => [
                'keywords' => $news->getKeywords(),
                'description' => $news->getDescription(),
                'alternativeTitle' => $news->getAlternativeTitle(),
            ],
            'pathSegment' => $news->getPathSegment(),
        ];
    }

    private function serializeDate(?DateTime $date): string|null
    {
        return $date ? $this->viewHelperInvoker->invoke(
            DateViewHelper::class,
            ['date' => $date, 'format' => $this->settings['dateFormat']],
            $this->renderingContext
        ) : null;
    }

    private function serializeDateTime(?DateTime $dateTime): string|null
    {
        return $dateTime ? $this->viewHelperInvoker->invoke(
            DateViewHelper::class,
            ['date' => $dateTime, 'format' => $this->settings['dateTimeFormat']],
            $this->renderingContext
        ) : null;
    }

    /**
     * @param Category[] $categories
     */
    private function serializeCategories(array $categories): array
    {
        return array_map(function (Category $category) {
            return $this->serializeCategory($category);
        }, $categories);
    }

    /**
     * @param Tag[] $tags
     */
    private function serializeTags(array $tags): array
    {
        return array_map(function (Tag $tag) {
            return $this->serializeTag($tag);
        }, $tags);
    }

    /**
     * @param FileReference[] $files
     */
    private function serializeFiles(array $files): array
    {
        return array_map(function (FileReference $file) {
            return $this->serializeFile($file);
        }, $files);
    }

    private function serializeFile(FileReference $file): array
    {
        $originalResource = $file->getOriginalResource();
        $processedFile = $this->fileUtility->processFile($originalResource);
        return $this->jsonDecoder->decode($processedFile);
    }
}
