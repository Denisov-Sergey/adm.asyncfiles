<?php
/**
 * Подключение модуля adm.asyncfiles.
 */

// Подключаем Composer autoloader, если он существует
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
