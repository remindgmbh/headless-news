<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Utility;

use Closure;
use DateTime;
use FriendsOfTYPO3\Headless\Json\JsonDecoder;
use FriendsOfTYPO3\Headless\Utility\FileUtility;
use GeorgRinger\News\Domain\Model\Category;
use GeorgRinger\News\Domain\Model\FileReference;
use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Model\Tag;
use GeorgRinger\News\ViewHelpers\LinkViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;

class JsonUtility
{
    private FileUtility $fileUtility;
    private JsonDecoder $jsonDecoder;
    private RenderingContextInterface $renderingContext;
    private ViewHelperInvoker $viewHelperInvoker;
    private Closure $renderChildrenClosure;

    private array $settings;

    public function __construct(RenderingContextInterface $renderingContext, Closure $renderChildrenClosure, array $settings)
    {
        $this->fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $this->jsonDecoder = GeneralUtility::makeInstance(JsonDecoder::class);
        $this->renderingContext = $renderingContext;
        $this->renderChildrenClosure = $renderChildrenClosure;
        $this->viewHelperInvoker = $renderingContext->getViewHelperInvoker();
        $this->settings = $settings;
    }

    public function processListNews(News $news): array
    {
        $result = $this->processNews($news);
        $result['media'] = $this->processFiles($news->getMediaPreviews());
        $result['slug'] = $this->viewHelperInvoker->invoke(
            LinkViewHelper::class,
            ['newsItem' => $news, 'settings' => $this->settings, 'uriOnly' => true],
            $this->renderingContext,
            $this->renderChildrenClosure
        );
        return $result;
    }

    public function processDetailNews(News $news): array
    {
        $result = $this->processNews($news);
        $result['media'] = $this->processFiles($news->getMediaNonPreviews());
        $result['bodytext'] = $this->viewHelperInvoker->invoke(
            HtmlViewHelper::class,
            ['parseFuncTSPath' => 'lib.parseFunc_links'],
            $this->renderingContext,
            function () use ($news) {
                return $news->getBodytext();
            }
        );
        return $result;
    }

    private function processNews(News $news): array
    {
        return [
            'uid' => $news->getUid(),
            'title' => $news->getTitle(),
            'teaser' => $news->getTeaser(),
            'isTopNews' => $news->getIstopnews(),
            'crDate' => $this->processDate($news->getCrdate()),
            'tstamp' => $this->processDate($news->getTstamp()),
            'datetime' => $this->processDate($news->getDatetime()),
            'archive' => $this->processDateTime($news->getArchive()),
            'author' => [
                'name' => $news->getAuthor(),
                'email' => $news->getAuthorEmail(),
            ],
            'relatedFiles' => $news->getRelatedFiles()->count(),
            'categories' => $this->processCategories($news->getCategories()->toArray()),
            'tags' => $this->processTags($news->getTags()->toArray()),
            'metaData' => [
                'keywords' => $news->getKeywords(),
                'description' => $news->getDescription(),
                'alternativeTitle' => $news->getAlternativeTitle(),
            ],
            'pathSegment' => $news->getPathSegment(),
        ];
    }

    private function processDate(?DateTime $date): string|null
    {
        return $date ? $this->viewHelperInvoker->invoke(
            DateViewHelper::class,
            ['date' => $date, 'format' => $this->settings['dateFormat']],
            $this->renderingContext
        ) : null;
    }

    private function processDateTime(?DateTime $dateTime): string|null
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
    private function processCategories(array $categories): array
    {
        return array_map(function (Category $category) {
            return $this->processCategory($category);
        }, $categories);
    }

    private function processCategory(Category $category): array
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

    /**
     * @param Tag[] $tags
     */
    private function processTags(array $tags): array
    {
        return array_map(function (Tag $tag) {
            return $this->processTag($tag);
        }, $tags);
    }

    private function processTag(Tag $tag): array
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

    /**
     * @param FileReference[] $files
     */
    private function processFiles(array $files): array
    {
        return array_map(function (FileReference $file) {
            return $this->processFile($file);
        }, $files);
    }

    private function processFile(FileReference $file): array
    {
        $originalResource = $file->getOriginalResource();
        $processedFile = $this->fileUtility->processFile($originalResource);
        return $this->jsonDecoder->decode($processedFile);
    }
}
