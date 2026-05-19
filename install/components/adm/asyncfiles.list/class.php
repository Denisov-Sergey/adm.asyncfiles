<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class AsyncFilesListComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        if (!$USER->IsAuthorized()) {
            ShowError('Пожалуйста, авторизуйтесь для просмотра файлов.');
            return;
        }

        $this->arResult['AJAX_URL'] = '/local/modules/adm.asyncfiles/ajax.php?action=list';
        $this->includeComponentTemplate();
    }
}
