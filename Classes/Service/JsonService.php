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
use GeorgRinger\News\ViewHelpers\LinkViewHelper;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;

class JsonService
{
    private ViewHelperInvoker $viewHelperInvoker;
    private RenderingContext $renderingContext;
    private array $settings;

    public function __construct(
        private readonly FileUtility $fileUtility,
        private readonly JsonDecoder $jsonDecoder,
        private readonly ContentObjectRenderer $contentObjectRenderer,
        ConfigurationManagerInterface $configurationManager,
        RenderingContextFactory $renderingContextFactory,
    ) {
        $this->renderingContext = $renderingContextFactory->create();
        $this->viewHelperInvoker = $this->renderingContext->getViewHelperInvoker();
        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
        );
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
            'uid' => $category->getUid(),
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
            'uid' => $tag->getUid(),
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
            'crDate' => $news->getCrdate()?->format(DateTime::ISO8601),
            'tstamp' => $news->getTstamp()?->format(DateTime::ISO8601),
            'datetime' => $news->getDatetime()?->format(DateTime::ISO8601),
            'archive' => $news->getArchive()?->format(DateTime::ISO8601),
            'author' => [
                'name' => $news->getAuthor(),
                'email' => $news->getAuthorEmail(),
            ],
            'relatedFiles' => $this->serializeFiles($news->getRelatedFiles()->toArray()),
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
