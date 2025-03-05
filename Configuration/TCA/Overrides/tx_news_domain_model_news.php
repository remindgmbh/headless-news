<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

ExtensionManagementUtility::addTCAcolumns(
    'tx_news_domain_model_news',
    [
        'tx_headless_news_no_search' => [
            'config' => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'items' => [
                    [
                        'invertStateDisplay' => true,
                        'label' => '',
                    ],
                ],
                'renderType' => 'checkboxToggle',
                'type' => 'check',
            ],
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.no_search',
        ],
        'tx_headless_news_search_image' => [
            'config' => [
                'allowed' => 'common-image-types',
                'maxitems' => 1,
                'type' => 'file',
            ],
            'label' => 'LLL:EXT:rmnd_headless_news/Resources/Private/Language/locallang.xlf:tx_headless_news_search_image',
        ],
     ]
);

ExtensionManagementUtility::addFieldsToPalette(
    'tx_news_domain_model_news',
    'search',
    'tx_headless_news_search_image,--linebreak--,tx_headless_news_no_search',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tx_news_domain_model_news',
    '--palette--;LLL:EXT:rmnd_headless_news/Resources/Private/Language/locallang.xlf:palettes.search;search',
);
