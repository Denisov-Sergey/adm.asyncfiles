<?php

/**
 * Публичная страница-пример для размещения компонентов загрузки и списка файлов.
 *
 * Для использования скопируйте этот файл в корень сайта или
 * в нужный раздел (например, /asyncfiles/).
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle('Загрузка и обработка файлов');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <?php
    // Компонент загрузки файла
    $APPLICATION->IncludeComponent(
        'adm:asyncfiles.upload',
        '.default',
        [],
        false
    );
    ?>

    <hr style="margin: 32px 0; border: none; border-top: 1px solid #e2e8f0;">

    <?php
    // Компонент списка загруженных файлов
    $APPLICATION->IncludeComponent(
        'adm:asyncfiles.list',
        '.default',
        [],
        false
    );
    ?>
</div>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
