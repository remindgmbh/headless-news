<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\EventListener;

use Remind\Headless\Service\JsonService;
use Remind\HeadlessSolr\Event\ModifySearchDocumentEvent;

final class ModifySolrSearchDocumentEventListener extends AbstractModifySolrDocumentEventListener
{
    private ?JsonService $jsonService = null;

    public function injectJsonService(JsonService $jsonService): void
    {
        $this->jsonService = $jsonService;
    }

    public function __invoke(ModifySearchDocumentEvent $event): void
    {
        $searchResult = $event->getSearchResult();

        $document = $this->addLink($searchResult, $event->getDocument());

        $imageUid = $searchResult['searchImage_intS'] ?? $searchResult['falMedia_intS'] ?? null;

        if ($imageUid) {
            $document['image'] = $this->jsonService?->processImage($imageUid);
        }

        $event->setDocument($document);
    }
}
