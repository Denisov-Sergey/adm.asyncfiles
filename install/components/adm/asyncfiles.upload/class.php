<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\CurrentUser;

class AsyncFilesUploadComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            ShowError('Пожалуйста, авторизуйтесь для загрузки файлов.');
            return;
        }


        $this->arResult['SESSID'] = bitrix_sessid();
        $this->arResult['AJAX_URL'] = '/local/modules/adm.asyncfiles/ajax.php?action=upload';

        $this->includeComponentTemplate();
    }
}
