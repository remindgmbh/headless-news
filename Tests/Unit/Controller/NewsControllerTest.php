<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Tests\Unit\Controller;

use GeorgRinger\News\Domain\Repository\CategoryRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use GeorgRinger\News\Domain\Repository\TagRepository;
use GeorgRinger\News\Event\CreateDemandObjectFromSettingsEvent;
use GeorgRinger\News\Event\NewsDateMenuActionEvent;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionProperty;
use Remind\HeadlessNews\Controller\NewsController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(NewsController::class)]
class NewsControllerTest extends UnitTestCase
{
    #[Test]
    public function dateMenuActionBuildsYearsMonthsCountsAndActiveFlags(): void
    {
        $newsRepository = $this->createMock(NewsRepository::class);
        $newsRepository
            ->expects(self::once())
            ->method('findDemanded')
            ->willReturn([]);
        $newsRepository
            ->expects(self::once())
            ->method('countByDate')
            ->willReturn([
                'single' => [
                    2024 => [12 => 1],
                    2025 => [1 => 2, 2 => 3],
                ],
                'total' => [
                    2024 => 1,
                    2025 => 5,
                ],
            ]);

        $subject = new NewsController(
            $newsRepository,
            $this->createMock(CategoryRepository::class),
            $this->createMock(TagRepository::class)
        );

        $this->setProperty($subject, 'settings', [
            'dateField' => 'datetime',
            'disableOverrideDemand' => 1,
            'listPid' => 123,
            'orderDirection' => 'desc',
            'templateLayout' => 'default',
        ]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof CreateDemandObjectFromSettingsEvent) {
                    return $event;
                }

                if ($event instanceof NewsDateMenuActionEvent) {
                    return $event;
                }

                return $event;
            });
        $this->setProperty($subject, 'eventDispatcher', $eventDispatcher);

        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder
            ->method('reset')
            ->willReturnSelf();
        $uriBuilder
            ->method('setTargetPageUid')
            ->willReturnSelf();
        $uriBuilder
            ->method('build')
            ->willReturn('/news/list');
        $uriBuilder
            ->method('uriFor')
            ->willReturnCallback(static function (
                ?string $_actionName = null,
                ?array $controllerArguments = null
            ): string {
                unset($_actionName);
                $overwriteDemand = $controllerArguments['overwriteDemand'] ?? [];

                if ($overwriteDemand === []) {
                    return '/news/all';
                }

                if (isset($overwriteDemand['year'], $overwriteDemand['month'])) {
                    return '/news/year/' . $overwriteDemand['year'] . '/month/' . $overwriteDemand['month'];
                }

                if (isset($overwriteDemand['year'])) {
                    return '/news/year/' . $overwriteDemand['year'];
                }

                return '/news/all';
            });

        $this->setProperty($subject, 'uriBuilder', $uriBuilder);

        $serverRequest = (new ServerRequest('/'))
            ->withAttribute('extbase', new ExtbaseRequestParameters(NewsController::class));
        $request = new Request($serverRequest);
        $this->setProperty($subject, 'request', $request);

        $view = new DateMenuViewDouble();
        $this->setProperty($subject, 'view', $view);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory
            ->method('createResponse')
            ->willReturnCallback(static fn (int $code = 200): Response => new Response($code));
        $this->setProperty($subject, 'responseFactory', $responseFactory);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory
            ->method('createStream')
            ->willReturnCallback(static fn (string $content = '') => Utils::streamFor($content));
        $this->setProperty($subject, 'streamFactory', $streamFactory);

        $response = $subject->dateMenuAction(['year' => 2025, 'month' => '2']);

        $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('/news/list', $result['settings']['listLink']);
        self::assertSame('default', $result['settings']['templateLayout']);

        self::assertSame(6, $result['years']['all']['count']);
        self::assertFalse($result['years']['all']['active']);
        self::assertSame('/news/all', $result['years']['all']['link']);

        self::assertCount(2, $result['years']['list']);

        $yearsByTitle = [];
        foreach ($result['years']['list'] as $yearItem) {
            $yearsByTitle[(int) $yearItem['title']] = $yearItem;
        }

        self::assertArrayHasKey(2025, $yearsByTitle);
        self::assertArrayHasKey(2024, $yearsByTitle);

        self::assertFalse($yearsByTitle[2025]['active']);
        self::assertSame(5, $yearsByTitle[2025]['count']);
        self::assertSame('/news/year/2025', $yearsByTitle[2025]['link']);
        self::assertCount(2, $yearsByTitle[2025]['months']);

        self::assertFalse($yearsByTitle[2025]['months'][0]['active']);
        self::assertSame('/news/year/2025/month/1', $yearsByTitle[2025]['months'][0]['link']);

        self::assertTrue($yearsByTitle[2025]['months'][1]['active']);
        self::assertSame('/news/year/2025/month/2', $yearsByTitle[2025]['months'][1]['link']);

        self::assertFalse($yearsByTitle[2024]['active']);
        self::assertCount(1, $yearsByTitle[2024]['months']);
    }

    private function setProperty(object $subject, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($subject, $propertyName);
        $reflectionProperty->setValue($subject, $value);
    }
}
