<?php

declare(strict_types=1);

defined('TYPO3') || die;

use Remind\Headless\Utility\TcaUtility;

TcaUtility::addPageConfigFlexForm('FILE:EXT:rmnd_headless_news/Configuration/FlexForms/Config.xml');
