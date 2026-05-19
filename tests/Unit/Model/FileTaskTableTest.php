<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Tests\Unit\Model;

use Adm\Asyncfiles\Model\FileTaskTable;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для ORM-класса FileTaskTable.
 *
 * Проверяет корректность маппинга: имя таблицы, набор полей,
 * обязательность и значения по умолчанию.
 *
 * Тесты не требуют подключения к БД — проверяется только описание схемы.
 *
 * @package Adm\Asyncfiles\Tests\Unit\Model
 */
final class FileTaskTableTest extends TestCase
{
    /**
     * Проверяет имя таблицы в базе данных.
     */
    public function testTableName(): void
    {
        $this->assertSame('adm_asyncfiles_task', FileTaskTable::getTableName());
    }

    /**
     * Проверяет что карта полей содержит все необходимые поля.
     */
    public function testMapContainsAllRequiredFields(): void
    {
        $map = FileTaskTable::getMap();
        $fieldNames = [];

        foreach ($map as $field) {
            $fieldNames[] = $field->getName();
        }

        $expectedFields = [
            'ID',
            'B_FILE_ID',
            'ORIGINAL_NAME',
            'FILE_SIZE',
            'STATUS',
            'PROCESSED_HASH',
            'CREATED_AT',
            'UPDATED_AT',
        ];

        foreach ($expectedFields as $expectedField) {
            $this->assertContains(
                $expectedField,
                $fieldNames,
                "Поле {$expectedField} должно присутствовать в карте ORM"
            );
        }
    }

    /**
     * Проверяет что ID является автоинкрементным первичным ключом.
     */
    public function testIdFieldIsPrimaryAndAutocomplete(): void
    {
        $map = FileTaskTable::getMap();
        $idField = null;

        foreach ($map as $field) {
            if ($field->getName() === 'ID') {
                $idField = $field;
                break;
            }
        }

        $this->assertNotNull($idField, 'Поле ID должно существовать');
        $this->assertTrue($idField->isPrimary(), 'Поле ID должно быть первичным ключом');
        $this->assertTrue($idField->isAutocomplete(), 'Поле ID должно быть автоинкрементным');
    }

    /**
     * Проверяет что B_FILE_ID обязательное поле.
     */
    public function testBFileIdIsRequired(): void
    {
        $map = FileTaskTable::getMap();

        foreach ($map as $field) {
            if ($field->getName() === 'B_FILE_ID') {
                $this->assertTrue($field->isRequired(), 'B_FILE_ID должно быть обязательным');
                return;
            }
        }

        $this->fail('Поле B_FILE_ID не найдено');
    }

    /**
     * Проверяет количество полей в карте (8 полей).
     */
    public function testMapFieldCount(): void
    {
        $map = FileTaskTable::getMap();
        $this->assertCount(8, $map, 'Карта должна содержать 8 полей');
    }

    /**
     * Проверяет что поле STATUS имеет значение по умолчанию PENDING.
     */
    public function testStatusDefaultValue(): void
    {
        $map = FileTaskTable::getMap();

        foreach ($map as $field) {
            if ($field->getName() === 'STATUS') {
                $this->assertSame('PENDING', $field->getDefaultValue());
                return;
            }
        }

        $this->fail('Поле STATUS не найдено');
    }
}
