<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event\Listener;

use Remind\Headless\Service\JsonService;
use Remind\Headless\Utility\ConfigUtility;
use Remind\HeadlessSolr\Event\ModifySearchDocumentEvent;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

final class ModifySearchDocument
{
    private int $detailPageUid = 0;

    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly LinkFactory $linkFactory,
        private readonly JsonService $jsonService,
    ) {
        $pageConfig = ConfigUtility::getConfig();
        $this->detailPageUid = (int) $pageConfig['news']['detailPage'] ?? 0;
    }

    public function __invoke(ModifySearchDocumentEvent $event): void
    {
        $searchResult = $event->getSearchResult();
        if ($searchResult->getType() === 'tx_news_domain_model_news') {
            $document = $event->getDocument();
            $fields = $searchResult->getFields();

            if (isset($fields['internalUrl_stringS'])) {
                $document['link'] = $this->linkFactory
                    ->createUri($fields['internalUrl_stringS'])
                    ->getUrl();
            } elseif (isset($fields['externalUrl_stringS'])) {
                $document['link'] = $fields['externalUrl_stringS'];
            } else {
                $uid = $fields['uid'];
                $document['link'] = $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid($this->detailPageUid)
                    ->uriFor('detail', ['news' => $uid], 'News', 'news', 'pi1');
            }

            $imageUid = $fields['searchImage_intS'] ?? $fields['falMedia_intS'] ?? null;

            if ($imageUid) {
                $document['image'] = $this->jsonService->processImage($imageUid);
            }

            $event->setDocument($document);
        }
    }
}
