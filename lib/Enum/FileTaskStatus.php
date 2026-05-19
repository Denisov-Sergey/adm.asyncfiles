<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Enum;

/**
 * Статусы задачи обработки файла.
 */
enum FileTaskStatus: string
{
    /** Задача создана, ожидает обработки в очереди */
    case Pending = 'PENDING';

    /** Задача взята воркером и обрабатывается */
    case Processing = 'PROCESSING';

    /** Обработка завершена успешно */
    case Completed = 'COMPLETED';

    /** Обработка завершилась с ошибкой */
    case Error = 'ERROR';

    /**
     * Возвращает человекочитаемую метку статуса на русском языке.
     *
     * @return string Метка статуса
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'В очереди',
            self::Processing => 'Обрабатывается',
            self::Completed => 'Завершено',
            self::Error => 'Ошибка',
        };
    }

    /**
     * Возвращает CSS-цвет для отображения статуса в интерфейсе.
     *
     * @return string HEX-код цвета
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => '#e67e22',
            self::Processing => '#3498db',
            self::Completed => '#27ae60',
            self::Error => '#e74c3c',
        };
    }

    /**
     * Проверяет, допустимо ли указанное значение в качестве статуса.
     *
     * @param string $value Проверяемое значение
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
