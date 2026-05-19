<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Controller;

use Adm\Asyncfiles\Config\RabbitMQConfig;
use Adm\Asyncfiles\Enum\FileTaskStatus;
use Adm\Asyncfiles\Model\FileTaskTable;
use Adm\Asyncfiles\Service\RabbitMQService;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CFile;
use Exception;

/**
 * Контроллер загрузки файлов и получения списка задач.
 */
class FileApiController extends BaseApiController
{
    /**
     * Загрузка файла и постановка задачи в очередь.
     */
    public function uploadAction(): never
    {
        if (!$this->user->IsAuthorized()) {
            $this->sendError('Необходима авторизация', 401);
        }

        try {
            $file = $this->request->getFile('file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->sendError('Ошибка загрузки файла');
            }

            $file['MODULE_ID'] = 'adm.asyncfiles';
            $fileId = CFile::SaveFile($file, 'asyncfiles');

            if (!$fileId) {
                $this->sendError('Не удалось сохранить файл в b_file');
            }

            $originalName = $file['name'] ?? 'unknown';
            $fileSize = (int)($file['size'] ?? 0);


            $result = FileTaskTable::add([
                'B_FILE_ID' => $fileId,
                'ORIGINAL_NAME' => $originalName,
                'FILE_SIZE' => $fileSize,
                'STATUS' => FileTaskStatus::Pending->value,
                'CREATED_AT' => new BitrixDateTime(),
                'UPDATED_AT' => new BitrixDateTime(),
            ]);

            if (!$result->isSuccess()) {
                CFile::Delete($fileId);
                $errorMessages = implode(', ', $result->getErrorMessages());
                $this->sendError("Не удалось создать задачу: {$errorMessages}");
            }

            $taskId = $result->getId();


            $rabbitConfig = RabbitMQConfig::fromEnvironment();
            $rabbitService = new RabbitMQService($rabbitConfig);
            $rabbitService->publish([
                'task_id' => $taskId,
                'file_id' => $fileId,
                'user_id' => $this->user->GetID(),
            ]);

            $this->sendSuccess([
                'task_id' => $taskId,
                'message' => 'Файл успешно загружен. Задача поставлена в очередь обработки.',
            ]);
        } catch (Exception $exception) {
            $this->sendError('Внутренняя ошибка сервера: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Список загруженных файлов и их статусов.
     */
    public function listAction(): never
    {
        if (!$this->user->IsAuthorized()) {
            $this->sendError('Необходима авторизация', 401);
        }

        try {
            $tasks = FileTaskTable::query()
                ->setSelect(['ID', 'B_FILE_ID', 'ORIGINAL_NAME', 'FILE_SIZE', 'STATUS', 'CREATED_AT'])
                ->setOrder(['ID' => 'DESC'])
                ->fetchAll();

            $responseData = [];
            foreach ($tasks as $task) {
                $status = FileTaskStatus::tryFrom($task['STATUS']);

                $downloadUrl = null;
                if ($status === FileTaskStatus::Completed) {
                    $downloadUrl = CFile::GetPath($task['B_FILE_ID']);
                }

                $responseData[] = [
                    'id' => (int)$task['ID'],
                    'status' => $task['STATUS'],
                    'status_label' => $status?->label() ?? $task['STATUS'],
                    'status_color' => $status?->color() ?? '#999',
                    'created_at' => $task['CREATED_AT'] ? $task['CREATED_AT']->format('Y-m-d H:i:s') : null,
                    'file_name' => $task['ORIGINAL_NAME'],
                    'file_size' => $this->formatFileSize((int)$task['FILE_SIZE']),
                    'download_url' => $downloadUrl,
                ];
            }

            $this->sendSuccess($responseData);
        } catch (Exception $exception) {
            $this->sendError('Внутренняя ошибка сервера: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Форматирует размер файла (bytes → KB/MB/GB).
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int)floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
