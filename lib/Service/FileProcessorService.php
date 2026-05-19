<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Service;

use Adm\Asyncfiles\Enum\FileTaskStatus;
use Adm\Asyncfiles\Model\FileTaskTable;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CFile;
use Exception;

/**
 * Обработка файловых задач из очереди RabbitMQ.
 */
class FileProcessorService
{
    /**
     * Обработка задачи: PENDING → PROCESSING → COMPLETED/ERROR.
     *
     * @param int $taskId ID задачи
     * @return bool
     */
    public function processTask(int $taskId): bool
    {
        try {
            // Берём только PENDING-задачу
            $task = FileTaskTable::query()
                ->setSelect(['ID', 'B_FILE_ID', 'STATUS'])
                ->where('ID', $taskId)
                ->where('STATUS', FileTaskStatus::Pending->value)
                ->fetch();

            if (!$task) {
                return false;
            }


            FileTaskTable::update($taskId, [
                'STATUS' => FileTaskStatus::Processing->value,
                'UPDATED_AT' => new BitrixDateTime(),
            ]);


            $processedHash = $this->processFile((int)$task['B_FILE_ID']);


            FileTaskTable::update($taskId, [
                'STATUS' => FileTaskStatus::Completed->value,
                'PROCESSED_HASH' => $processedHash,
                'UPDATED_AT' => new BitrixDateTime(),
            ]);

            return true;
        } catch (Exception $exception) {
            // При ошибке переводим в ERROR
            try {
                FileTaskTable::update($taskId, [
                    'STATUS' => FileTaskStatus::Error->value,
                    'UPDATED_AT' => new BitrixDateTime(),
                ]);
            } catch (Exception) {
            }

            return false;
        }
    }

    /**
     * Вычисление SHA-256 хеша файла.
     *
     * @param int $fileId ID в b_file
     * @return string SHA-256 хеш
     * @throws Exception
     */
    private function processFile(int $fileId): string
    {
        $fileArray = CFile::GetFileArray($fileId);

        if (!$fileArray) {
            throw new Exception("Файл с ID {$fileId} не найден в b_file");
        }

        $filePath = $_SERVER['DOCUMENT_ROOT'] . $fileArray['SRC'];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("Файл не доступен для чтения: {$filePath}");
        }

        // Имитация длительной обработки
        sleep(rand(2, 5));

        $hash = hash_file('sha256', $filePath);

        if ($hash === false) {
            throw new Exception("Не удалось вычислить хеш файла: {$filePath}");
        }

        return $hash;
    }
}
