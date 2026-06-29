<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Tests\Unit\EventListener;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Remind\HeadlessNews\EventListener\ModifySolrSearchDocumentEventListener;
use Remind\HeadlessSolr\Event\ModifySearchDocumentEvent;
use RuntimeException;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

#[CoversClass(ModifySolrSearchDocumentEventListener::class)]
class ModifySolrSearchDocumentEventListenerTest extends UnitTestCase
{
    #[Test]
    public function invokePrefersInternalUrlOverExternalUrl(): void
    {
        $linkResult = $this->createMock(LinkResultInterface::class);
        $linkResult
            ->expects(self::once())
            ->method('getUrl')
            ->willReturn('/internal-link');

        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory
            ->expects(self::once())
            ->method('createUri')
            ->with('/internal-source')
            ->willReturn($linkResult);

        $subject = $this->createSubject($linkFactory, 123);

        $searchResult = new SearchResult([
            'externalUrl_stringS' => 'https://external.example/news',
            'internalUrl_stringS' => '/internal-source',
            'type' => 'tx_news_domain_model_news',
            'uid' => 123,
        ], [], []);

        $event = new ModifySearchDocumentEvent(
            ['title' => 'News'],
            $searchResult,
            $this->createMock(RenderingContextInterface::class)
        );

        $subject($event);

        self::assertSame('/internal-link', $event->getDocument()['link']);
    }

    #[Test]
    public function invokeUsesExternalUrlWhenNoInternalUrlExists(): void
    {
        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory
            ->expects(self::never())
            ->method('createUri');

        $subject = $this->createSubject($linkFactory, 123);

        $searchResult = new SearchResult([
            'externalUrl_stringS' => 'https://external.example/news',
            'type' => 'tx_news_domain_model_news',
            'uid' => 321,
        ], [], []);

        $event = new ModifySearchDocumentEvent(
            ['title' => 'News'],
            $searchResult,
            $this->createMock(RenderingContextInterface::class)
        );

        $subject($event);

        self::assertSame('https://external.example/news', $event->getDocument()['link']);
    }

    #[Test]
    public function invokeBuildsFallbackDetailLinkWhenNoInternalAndExternalUrlExist(): void
    {
        $linkResult = $this->createMock(LinkResultInterface::class);
        $linkResult
            ->expects(self::once())
            ->method('getUrl')
            ->willReturn('/detail-link');

        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory
            ->expects(self::once())
            ->method('createUri')
            ->with(self::callback(static function (string $url): bool {
                return str_contains($url, 'tx_news_pi1[action]=detail')
                    && str_contains($url, 'tx_news_pi1[controller]=News')
                    && str_contains($url, '123?')
                    && str_contains($url, 'tx_news_pi1[news]=99');
            }))
            ->willReturn($linkResult);

        $subject = $this->createSubject($linkFactory, 123);

        $searchResult = new SearchResult([
            'type' => 'tx_news_domain_model_news',
            'uid' => 99,
        ], [], []);

        $event = new ModifySearchDocumentEvent(
            ['title' => 'News'],
            $searchResult,
            $this->createMock(RenderingContextInterface::class)
        );

        $subject($event);

        self::assertSame('/detail-link', $event->getDocument()['link']);
    }

    private function createSubject(LinkFactory $linkFactory, int $detailPageUid): ModifySolrSearchDocumentEventListener
    {
        $reflectionClass = new ReflectionClass(ModifySolrSearchDocumentEventListener::class);

        /** @var ModifySolrSearchDocumentEventListener $subject */
        $subject = $reflectionClass->newInstanceWithoutConstructor();

        $this->setPrivateProperty($subject, 'linkFactory', $linkFactory);
        $this->setPrivateProperty($subject, 'detailPageUid', $detailPageUid);

        return $subject;
    }

    private function setPrivateProperty(object $subject, string $propertyName, mixed $value): void
    {
        $reflectionClass = new ReflectionClass($subject);

        while (
            !$reflectionClass->hasProperty($propertyName)
            && $reflectionClass->getParentClass()
        ) {
            $reflectionClass = $reflectionClass->getParentClass();
        }

        if (!$reflectionClass->hasProperty($propertyName)) {
            throw new RuntimeException('Property not found: ' . $propertyName);
        }

        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setValue($subject, $value);
    }
}
