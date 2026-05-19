<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Model;

use Adm\Asyncfiles\Enum\FileTaskStatus;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-класс таблицы adm_asyncfiles_task.
 */
class FileTaskTable extends DataManager
{
    /**
     * Возвращает имя таблицы в базе данных.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'adm_asyncfiles_task';
    }

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => 'ID задачи',
                ]
            ),
            new IntegerField(
                'B_FILE_ID',
                [
                    'required' => true,
                    'title' => 'ID файла в таблице b_file',
                ]
            ),
            new StringField(
                'ORIGINAL_NAME',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateOriginalName'],
                    'title' => 'Оригинальное имя загруженного файла',
                ]
            ),
            new IntegerField(
                'FILE_SIZE',
                [
                    'required' => true,
                    'default_value' => 0,
                    'title' => 'Размер файла в байтах',
                ]
            ),
            new StringField(
                'STATUS',
                [
                    'required' => true,
                    'default_value' => FileTaskStatus::Pending->value,
                    'validation' => [__CLASS__, 'validateStatus'],
                    'title' => 'Статус обработки задачи',
                ]
            ),
            new StringField(
                'PROCESSED_HASH',
                [
                    'nullable' => true,
                    'validation' => [__CLASS__, 'validateProcessedHash'],
                    'title' => 'SHA-256 хеш обработанного файла',
                ]
            ),
            new DatetimeField(
                'CREATED_AT',
                [
                    'default_value' => static function (): DateTime {
                        return new DateTime();
                    },
                    'title' => 'Дата и время создания задачи',
                ]
            ),
            new DatetimeField(
                'UPDATED_AT',
                [
                    'default_value' => static function (): DateTime {
                        return new DateTime();
                    },
                    'title' => 'Дата и время последнего обновления',
                ]
            ),
        ];
    }

    /** @return array */
    public static function validateOriginalName(): array
    {
        return [
            new LengthValidator(1, 255),
        ];
    }

    /** @return array */
    public static function validateStatus(): array
    {
        return [
            new LengthValidator(1, 50),
        ];
    }

    /** @return array */
    public static function validateProcessedHash(): array
    {
        return [
            new LengthValidator(null, 64),
        ];
    }
}
