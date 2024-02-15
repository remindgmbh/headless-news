<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\EventListener;

use Remind\HeadlessSolr\Event\ModifySuggestionDocumentEvent;

final class ModifySolrSuggestionDocumentEventListener extends AbstractModifySolrDocumentEventListener
{
    public function __invoke(ModifySuggestionDocumentEvent $event): void
    {
        $event->setDocument($this->addLink($event->getSearchResult(), $event->getDocument()));
    }
}
