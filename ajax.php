<?php

declare(strict_types=1);

/**
 * Точка входа для AJAX-запросов модуля adm.asyncfiles.
 *
 * Принимает параметр action (upload|list) и делегирует
 * обработку соответствующему методу FileApiController.
 */

define('NO_KEEP_STATISTIC', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Adm\Asyncfiles\Controller\FileApiController;

Loader::includeModule('adm.asyncfiles');

header('Content-Type: application/json; charset=utf-8');

$request = Context::getCurrent()->getRequest();
$action = $request->get('action');

$controller = new FileApiController();

if ($action === 'upload') {
    $controller->uploadAction();
} elseif ($action === 'list') {
    $controller->listAction();
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'data' => null,
        'errors' => ['Неизвестное действие'],
    ], JSON_UNESCAPED_UNICODE);
}

\CMain::FinalActions();
die();
