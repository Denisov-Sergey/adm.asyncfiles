<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Tests\Unit\Enum;

use Adm\Asyncfiles\Enum\FileTaskStatus;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для Enum FileTaskStatus.
 *
 * Проверяет корректность значений, меток и вспомогательных методов.
 *
 * @package Adm\Asyncfiles\Tests\Unit\Enum
 */
final class FileTaskStatusTest extends TestCase
{
    /**
     * Проверяет, что Enum содержит ровно 4 статуса.
     */
    public function testHasExactlyFourCases(): void
    {
        $cases = FileTaskStatus::cases();
        $this->assertCount(4, $cases);
    }

    /**
     * Проверяет строковые значения каждого статуса.
     */
    public function testStringValues(): void
    {
        $this->assertSame('PENDING', FileTaskStatus::Pending->value);
        $this->assertSame('PROCESSING', FileTaskStatus::Processing->value);
        $this->assertSame('COMPLETED', FileTaskStatus::Completed->value);
        $this->assertSame('ERROR', FileTaskStatus::Error->value);
    }

    /**
     * Проверяет метод label() — возвращает непустую русскоязычную метку.
     */
    public function testLabelsAreNotEmpty(): void
    {
        foreach (FileTaskStatus::cases() as $status) {
            $label = $status->label();
            $this->assertNotEmpty($label, "Метка для {$status->value} не должна быть пустой");
        }
    }

    /**
     * Проверяет конкретные значения label() для каждого статуса.
     */
    public function testLabelValues(): void
    {
        $this->assertSame('В очереди', FileTaskStatus::Pending->label());
        $this->assertSame('Обрабатывается', FileTaskStatus::Processing->label());
        $this->assertSame('Завершено', FileTaskStatus::Completed->label());
        $this->assertSame('Ошибка', FileTaskStatus::Error->label());
    }

    /**
     * Проверяет метод color() — возвращает валидный HEX-цвет.
     */
    public function testColorsAreValidHex(): void
    {
        foreach (FileTaskStatus::cases() as $status) {
            $color = $status->color();
            $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $color, "Цвет для {$status->value} должен быть валидным HEX");
        }
    }

    /**
     * Проверяет метод isValid() — допустимые значения.
     */
    public function testIsValidReturnsTrueForValidValues(): void
    {
        $this->assertTrue(FileTaskStatus::isValid('PENDING'));
        $this->assertTrue(FileTaskStatus::isValid('PROCESSING'));
        $this->assertTrue(FileTaskStatus::isValid('COMPLETED'));
        $this->assertTrue(FileTaskStatus::isValid('ERROR'));
    }

    /**
     * Проверяет метод isValid() — недопустимые значения.
     */
    public function testIsValidReturnsFalseForInvalidValues(): void
    {
        $this->assertFalse(FileTaskStatus::isValid(''));
        $this->assertFalse(FileTaskStatus::isValid('UNKNOWN'));
        $this->assertFalse(FileTaskStatus::isValid('pending'));
        $this->assertFalse(FileTaskStatus::isValid('completed'));
    }

    /**
     * Проверяет создание Enum из строки через tryFrom().
     */
    public function testTryFromReturnsCorrectCase(): void
    {
        $status = FileTaskStatus::tryFrom('PENDING');
        $this->assertSame(FileTaskStatus::Pending, $status);
    }

    /**
     * Проверяет что tryFrom() возвращает null для невалидного значения.
     */
    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $status = FileTaskStatus::tryFrom('INVALID');
        $this->assertNull($status);
    }
}
