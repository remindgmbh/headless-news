<?php

declare(strict_types=1);

use GeorgRinger\News\Controller\CategoryController;
use GeorgRinger\News\Controller\NewsController;
use GeorgRinger\News\Controller\TagController;
use Remind\HeadlessNews\Controller\CategoryController as HeadlessCategoryController;
use Remind\HeadlessNews\Controller\NewsController as HeadlessNewsController;
use Remind\HeadlessNews\Controller\TagController as HeadlessTagController;

defined('TYPO3_MODE') || die('Access denied.');

(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][NewsController::class] = [
        'className' => HeadlessNewsController::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CategoryController::class] = [
        'className' => HeadlessCategoryController::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TagController::class] = [
        'className' => HeadlessTagController::class,
    ];
})();
