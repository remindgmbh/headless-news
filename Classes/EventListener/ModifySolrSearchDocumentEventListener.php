<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\EventListener;

use Remind\Headless\Service\FilesService;
use Remind\HeadlessSolr\Event\ModifySearchDocumentEvent;

final class ModifySolrSearchDocumentEventListener extends AbstractModifySolrDocumentEventListener
{
    private ?FilesService $filesService = null;

    public function injectFilesService(FilesService $filesService): void
    {
        $this->filesService = $filesService;
    }

    public function __invoke(ModifySearchDocumentEvent $event): void
    {
        $searchResult = $event->getSearchResult();

        $document = $this->addLink($searchResult, $event->getDocument());

        $imageUid = $searchResult['searchImage_intS'] ?? $searchResult['falMedia_intS'] ?? null;

        if ($imageUid) {
            $document['image'] = $this->filesService?->processImage($imageUid);
        }

        $event->setDocument($document);
    }
}
