<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Controller;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use CMain;
use CUser;

/**
 * Базовый API-контроллер: инициализация окружения, CSRF-проверка, JSON-ответы.
 */
abstract class BaseApiController
{
    /** @var HttpRequest Объект текущего HTTP-запроса */
    protected HttpRequest $request;

    /** @var CUser Объект текущего пользователя Битрикс */
    protected CUser $user;

    /**
     * Инициализация окружения и проверка CSRF.
     */
    public function __construct()
    {
        $this->request = Context::getCurrent()->getRequest();

        global $USER;
        $this->user = $USER;


        $method = $this->request->getRequestMethod();
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            if (!check_bitrix_sessid()) {
                $this->sendError('Невалидный CSRF-токен', 403);
            }
        }
    }

    /**
     * Успешный JSON-ответ.
     *
     * @param mixed $data Данные ответа
     * @return never
     */
    protected function sendSuccess(mixed $data = []): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE);
        CMain::FinalActions();
        die();
    }

    /**
     * JSON-ответ с ошибкой.
     *
     * @param string $message Сообщение об ошибке
     * @param int $code HTTP-код
     * @return never
     */
    protected function sendError(string $message, int $code = 400): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'data' => null,
            'errors' => [$message],
        ], JSON_UNESCAPED_UNICODE);
        CMain::FinalActions();
        die();
    }
}
