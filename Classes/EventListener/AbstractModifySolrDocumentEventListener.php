<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\EventListener;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use Remind\Headless\Utility\ConfigUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

abstract class AbstractModifySolrDocumentEventListener
{
    private int $detailPageUid = 0;

    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly LinkFactory $linkFactory,
    ) {
        $pageConfig = ConfigUtility::getRootPageConfig();
        $this->detailPageUid = (int) ($pageConfig['news']['detailPage'] ?? null);
    }

    /**
     * @param mixed[] $document
     * @return mixed[]
     */
    protected function addLink(SearchResult $searchResult, array $document): array
    {
        if ($searchResult->getType() === 'tx_news_domain_model_news') {
            if (isset($searchResult['internalUrl_stringS'])) {
                $document['link'] = $this->linkFactory
                    ->createUri($searchResult['internalUrl_stringS'])
                    ->getUrl();
            } elseif (isset($searchResult['externalUrl_stringS'])) {
                $document['link'] = $searchResult['externalUrl_stringS'];
            } else {
                $uid = $searchResult['uid'];
                $document['link'] = $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid($this->detailPageUid)
                    ->uriFor('detail', ['news' => $uid], 'News', 'news', 'pi1');
            }
        }
        return $document;
    }
}
