<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\EventListener;

use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Event\NewsDetailActionEvent;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;

final class NewsMetaDescriptionEventListener
{
    public function __construct(
        private readonly MetaTagManagerRegistry $metaTags,
    ) {
    }

    public function __invoke(NewsDetailActionEvent $event): void
    {
        $news = $event->getAssignedValues()['newsItem'] ?? null;

        if (
            $news instanceof News &&
            $news->getDescription() !== ''
        ) {
            $this->metaTags->getManagerForProperty('description')
                ->addProperty('description', $news->getDescription(), [], true);
        }
    }
}
