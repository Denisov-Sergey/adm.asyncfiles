<?php

declare(strict_types=1);

/**
 * Воркер обработки очереди файлов.
 * Запуск: php bin/worker.php
 */


define('NO_KEEP_STATISTIC', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('CHK_EVENT', true);


$documentRoot = getenv('DOCUMENT_ROOT') ?: ($_SERVER['DOCUMENT_ROOT'] ?? '');

if (empty($documentRoot)) {

    $documentRoot = dirname(__DIR__, 4);
}

$_SERVER['DOCUMENT_ROOT'] = $documentRoot;

$prologPath = $documentRoot . '/bitrix/modules/main/include/prolog_before.php';

// Ожидаем установки Битрикс (актуально при первом запуске Docker)
$maxAttempts = 60;
$attempt = 0;

while (!file_exists($prologPath)) {
    $attempt++;
    if ($attempt >= $maxAttempts) {
        fwrite(STDERR, "[FATAL] Не найден пролог Битрикс: {$prologPath}\n");
        fwrite(STDERR, "[FATAL] Битрикс не установлен или указан неверный DOCUMENT_ROOT.\n");
        exit(1);
    }
    fwrite(STDOUT, "[~] Ожидание установки Битрикс... (попытка {$attempt}/{$maxAttempts})\n");
    sleep(10);
}

require($prologPath);

use Bitrix\Main\Loader;
use Adm\Asyncfiles\Config\RabbitMQConfig;
use Adm\Asyncfiles\Service\RabbitMQService;
use Adm\Asyncfiles\Service\FileProcessorService;

// Loader кеширует результат — при неудаче выходим, Docker перезапустит процесс
if (!Loader::includeModule('adm.asyncfiles')) {
    fwrite(STDOUT, "[~] Модуль adm.asyncfiles ещё не установлен. Повторная попытка через перезапуск...\n");
    sleep(10);
    exit(1);
}

$isRunning = true;

// Graceful shutdown по SIGTERM/SIGINT
if (extension_loaded('pcntl')) {
    pcntl_async_signals(true);

    $shutdownHandler = static function (int $signal) use (&$isRunning): void {
        $signalName = $signal === SIGTERM ? 'SIGTERM' : 'SIGINT';
        fwrite(STDOUT, "\n[*] Получен сигнал {$signalName}. Завершаем после текущей задачи...\n");
        $isRunning = false;
    };

    pcntl_signal(SIGTERM, $shutdownHandler);
    pcntl_signal(SIGINT, $shutdownHandler);
}

fwrite(STDOUT, "[*] Воркер запускается...\n");
fwrite(STDOUT, "[*] Ожидание сообщений. Для выхода нажмите Ctrl+C\n");

try {
    $rabbitConfig = RabbitMQConfig::fromEnvironment();
    $rabbitService = new RabbitMQService($rabbitConfig);
    $processorService = new FileProcessorService();

    $callback = static function ($message) use ($processorService, &$isRunning): void {
        $messageBody = $message->body;
        fwrite(STDOUT, "[>] Получено сообщение: {$messageBody}\n");

        $data = json_decode($messageBody, true);

        if (!isset($data['task_id'])) {
            fwrite(STDERR, "[!] Невалидный формат сообщения — отсутствует task_id\n");
            $message->nack(false, false);
            return;
        }

        $taskId = (int)$data['task_id'];
        $success = $processorService->processTask($taskId);

        if ($success) {
            fwrite(STDOUT, "[✓] Задача #{$taskId} обработана успешно.\n");
            $message->ack();
        } else {
            fwrite(STDERR, "[✗] Задача #{$taskId} завершилась с ошибкой.\n");
            $message->nack(false, false);
        }
    };

    $rabbitService->consume($callback);
} catch (\Exception $exception) {
    fwrite(STDERR, "[FATAL] " . $exception->getMessage() . "\n");
    exit(1);
}
