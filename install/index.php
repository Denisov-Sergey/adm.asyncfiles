<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;

/**
 * Класс установки/удаления модуля adm.asyncfiles.
 */
class adm_asyncfiles extends CModule
{
    /** @var string Идентификатор модуля */
    public $MODULE_ID = 'adm.asyncfiles';

    /** @var string Версия модуля */
    public $MODULE_VERSION;

    /** @var string Дата версии модуля */
    public $MODULE_VERSION_DATE;

    /** @var string Название модуля */
    public $MODULE_NAME;

    /** @var string Описание модуля */
    public $MODULE_DESCRIPTION;

    /** @var string Имя партнера/разработчика */
    public $PARTNER_NAME;

    /** @var string Ссылка на сайт партнера/разработчика */
    public $PARTNER_URI;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Асинхронная загрузка файлов';
        $this->MODULE_DESCRIPTION = 'Сервис загрузки и асинхронной обработки файлов через RabbitMQ';
        $this->PARTNER_NAME = 'addamant.ru';
        $this->PARTNER_URI = 'https://addamant.ru';
    }

    /** Установка модуля. */
    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    /** Удаление модуля. */
    public function DoUninstall(): void
    {
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Создаёт таблицу adm_asyncfiles_task.
     */
    public function InstallDB(): bool
    {
        $connection = Application::getConnection();

        if (!$connection->isTableExists('adm_asyncfiles_task')) {
            $connection->queryExecute("
                CREATE TABLE adm_asyncfiles_task (
                    ID INT(11) NOT NULL AUTO_INCREMENT,
                    B_FILE_ID INT(11) NOT NULL,
                    ORIGINAL_NAME VARCHAR(255) NOT NULL DEFAULT '',
                    FILE_SIZE INT(11) NOT NULL DEFAULT 0,
                    STATUS VARCHAR(50) NOT NULL DEFAULT 'PENDING',
                    PROCESSED_HASH VARCHAR(64) NULL DEFAULT NULL,
                    CREATED_AT DATETIME NULL,
                    UPDATED_AT DATETIME NULL,
                    PRIMARY KEY (ID),
                    INDEX ix_status (STATUS),
                    INDEX ix_b_file_id (B_FILE_ID)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        return true;
    }

    /** Удаляет таблицу adm_asyncfiles_task. */
    public function UnInstallDB(): bool
    {
        $connection = Application::getConnection();

        if ($connection->isTableExists('adm_asyncfiles_task')) {
            $connection->queryExecute('DROP TABLE adm_asyncfiles_task');
        }

        return true;
    }

    /** @return bool */
    public function InstallEvents(): bool
    {
        return true;
    }

    /** @return bool */
    public function UnInstallEvents(): bool
    {
        return true;
    }

    /**
     * Копирует компоненты и публичные файлы при установке.
     */
    public function InstallFiles(): bool
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];

        // Компоненты
        $componentsPath = $documentRoot . '/local/components/adm';

        if (!is_dir($componentsPath)) {
            mkdir($componentsPath, 0775, true);
        }

        $sourceDir = __DIR__ . '/components/adm';

        if (is_dir($sourceDir)) {
            CopyDirFiles($sourceDir, $componentsPath, true, true);
        }

        // Публичная страница
        $publicSource = __DIR__ . '/public/asyncfiles.php';
        $publicTarget = $documentRoot . '/asyncfiles.php';

        if (file_exists($publicSource) && !file_exists($publicTarget)) {
            copy($publicSource, $publicTarget);
        }

        // AJAX-обработчик
        $moduleDir = dirname(__DIR__);
        $ajaxSource = $moduleDir . '/ajax.php';
        $ajaxTarget = $documentRoot . '/local/modules/adm.asyncfiles/ajax.php';


        $ajaxPublicTarget = $documentRoot . '/asyncfiles_ajax.php';
        if (file_exists($ajaxSource) && !file_exists($ajaxPublicTarget)) {
            copy($ajaxSource, $ajaxPublicTarget);
        }

        return true;
    }

    /**
     * Удаляет компоненты и публичные файлы при деинсталляции.
     */
    public function UnInstallFiles(): bool
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];

        // Компоненты
        $componentsToRemove = [
            $documentRoot . '/local/components/adm/asyncfiles.upload',
            $documentRoot . '/local/components/adm/asyncfiles.list',
        ];

        foreach ($componentsToRemove as $componentPath) {
            if (is_dir($componentPath)) {
                DeleteDirFilesEx(
                    str_replace($documentRoot, '', $componentPath)
                );
            }
        }

        // Публичные файлы
        $publicFiles = [
            $documentRoot . '/asyncfiles.php',
            $documentRoot . '/asyncfiles_ajax.php',
        ];

        foreach ($publicFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return true;
    }
}
