<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Tests\Unit\Service;

use DateTime;
use FriendsOfTYPO3\Headless\Json\JsonDecoder;
use FriendsOfTYPO3\Headless\Utility\FileUtility;
use GeorgRinger\News\Domain\Model\News;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use ReflectionProperty;
use Remind\HeadlessNews\Event\SerializeListNewsEvent;
use Remind\HeadlessNews\Event\SerializeNewsEvent;
use Remind\HeadlessNews\Service\JsonService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;

#[CoversClass(JsonService::class)]
class JsonServiceTest extends UnitTestCase
{
    #[Test]
    public function serializeListNewsReturnsSerializedDataWithLinkAndAppliesEventExtensions(): void
    {
        $viewHelperInvoker = $this->createMock(ViewHelperInvoker::class);
        $viewHelperInvoker
            ->expects(self::once())
            ->method('invoke')
            ->with(
                'GeorgRinger\\News\\ViewHelpers\\LinkViewHelper',
                self::callback(static function (array $arguments): bool {
                    return ($arguments['uriOnly'] ?? false) === true
                        && isset($arguments['newsItem'])
                        && ($arguments['settings'] ?? null) === ['foo' => 'bar'];
                }),
                self::isInstanceOf(RenderingContext::class),
                self::isCallable()
            )
            ->willReturn('/news/link');

        $renderingContext = $this->createMock(RenderingContext::class);
        $renderingContext
            ->method('getViewHelperInvoker')
            ->willReturn($viewHelperInvoker);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof SerializeNewsEvent) {
                    $values = $event->getValues();
                    $values['fromSerializeNewsEvent'] = true;
                    return $event->setValues($values);
                }

                if ($event instanceof SerializeListNewsEvent) {
                    $values = $event->getValues();
                    $values['fromSerializeListNewsEvent'] = true;
                    return $event->setValues($values);
                }

                return $event;
            });

        $news = $this->createMock(News::class);
        $news->method('getArchive')->willReturn(null);
        $news->method('getAuthorEmail')->willReturn('author@example.com');
        $news->method('getAuthor')->willReturn('Author');
        $news->method('getCategories')->willReturn(null);
        $news->method('getCrdate')->willReturn(new DateTime('2026-01-01 12:00:00'));
        $news->method('getDatetime')->willReturn(null);
        $news->method('getIstopnews')->willReturn(false);
        $news->method('getAlternativeTitle')->willReturn('Alternative title');
        $news->method('getDescription')->willReturn('Description');
        $news->method('getKeywords')->willReturn('keyword-a,keyword-b');
        $news->method('getPathSegment')->willReturn('news-item');
        $news->method('getRelatedFiles')->willReturn(null);
        $news->method('getTags')->willReturn(null);
        $news->method('getTeaser')->willReturn('Teaser text');
        $news->method('getTitle')->willReturn('News title');
        $news->method('getTstamp')->willReturn(new DateTime('2026-01-02 08:00:00'));
        $news->method('getUid')->willReturn(42);
        $news->method('getMediaPreviews')->willReturn([]);

        $reflectionClass = new ReflectionClass(JsonService::class);
        /** @var JsonService $subject */
        $subject = $reflectionClass->newInstanceWithoutConstructor();

        $this->setPrivateProperty($subject, 'fileUtility', $this->createMock(FileUtility::class));
        $this->setPrivateProperty($subject, 'jsonDecoder', $this->createMock(JsonDecoder::class));
        $this->setPrivateProperty($subject, 'contentObjectRenderer', $this->createMock(ContentObjectRenderer::class));
        $this->setPrivateProperty($subject, 'eventDispatcher', $eventDispatcher);
        $this->setPrivateProperty($subject, 'renderingContext', $renderingContext);
        $this->setPrivateProperty($subject, 'viewHelperInvoker', $viewHelperInvoker);
        $this->setPrivateProperty($subject, 'settings', ['foo' => 'bar']);
        $this->setPrivateProperty($subject, 'assetProcessingConfiguration', []);

        $result = $subject->serializeListNews($news);

        self::assertSame('/news/link', $result['link']);
        self::assertSame([], $result['media']);
        self::assertSame(42, $result['uid']);
        self::assertSame('News title', $result['title']);
        self::assertTrue($result['fromSerializeNewsEvent']);
        self::assertTrue($result['fromSerializeListNewsEvent']);
    }

    private function setPrivateProperty(object $subject, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($subject, $propertyName);
        $reflectionProperty->setValue($subject, $value);
    }
}
