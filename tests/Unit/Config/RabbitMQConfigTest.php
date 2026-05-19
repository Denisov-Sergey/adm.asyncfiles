<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Tests\Unit\Config;

use Adm\Asyncfiles\Config\RabbitMQConfig;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для readonly DTO RabbitMQConfig.
 *
 * Проверяет значения по умолчанию и загрузку из переменных окружения.
 *
 * @package Adm\Asyncfiles\Tests\Unit\Config
 */
final class RabbitMQConfigTest extends TestCase
{
    /**
     * Проверяет значения по умолчанию при создании без аргументов.
     */
    public function testDefaultValues(): void
    {
        $config = new RabbitMQConfig();

        $this->assertSame('rabbitmq', $config->host);
        $this->assertSame(5672, $config->port);
        $this->assertSame('bitrix', $config->user);
        $this->assertSame('bitrix', $config->password);
        $this->assertSame('file_processing_queue', $config->queueName);
    }

    /**
     * Проверяет создание с кастомными значениями.
     */
    public function testCustomValues(): void
    {
        $config = new RabbitMQConfig(
            host: 'custom-host',
            port: 5673,
            user: 'admin',
            password: 'secret',
            queueName: 'custom_queue',
        );

        $this->assertSame('custom-host', $config->host);
        $this->assertSame(5673, $config->port);
        $this->assertSame('admin', $config->user);
        $this->assertSame('secret', $config->password);
        $this->assertSame('custom_queue', $config->queueName);
    }

    /**
     * Проверяет загрузку из переменных окружения.
     */
    public function testFromEnvironmentWithEnvVars(): void
    {
        // Устанавливаем переменные окружения для теста
        putenv('RABBITMQ_HOST=test-host');
        putenv('RABBITMQ_PORT=5680');
        putenv('RABBITMQ_USER=testuser');
        putenv('RABBITMQ_PASSWORD=testpass');
        putenv('RABBITMQ_QUEUE=test_queue');

        try {
            $config = RabbitMQConfig::fromEnvironment();

            $this->assertSame('test-host', $config->host);
            $this->assertSame(5680, $config->port);
            $this->assertSame('testuser', $config->user);
            $this->assertSame('testpass', $config->password);
            $this->assertSame('test_queue', $config->queueName);
        } finally {
            // Очищаем переменные окружения
            putenv('RABBITMQ_HOST');
            putenv('RABBITMQ_PORT');
            putenv('RABBITMQ_USER');
            putenv('RABBITMQ_PASSWORD');
            putenv('RABBITMQ_QUEUE');
        }
    }

    /**
     * Проверяет fallback на значения по умолчанию, когда env не задан.
     */
    public function testFromEnvironmentFallbackDefaults(): void
    {
        // Убеждаемся что переменные не заданы
        putenv('RABBITMQ_HOST');
        putenv('RABBITMQ_PORT');
        putenv('RABBITMQ_USER');
        putenv('RABBITMQ_PASSWORD');
        putenv('RABBITMQ_QUEUE');

        $config = RabbitMQConfig::fromEnvironment();

        $this->assertSame('rabbitmq', $config->host);
        $this->assertSame(5672, $config->port);
        $this->assertSame('bitrix', $config->user);
        $this->assertSame('bitrix', $config->password);
        $this->assertSame('file_processing_queue', $config->queueName);
    }

    /**
     * Проверяет что порт корректно приводится к int.
     */
    public function testPortIsAlwaysInteger(): void
    {
        putenv('RABBITMQ_PORT=5680');

        try {
            $config = RabbitMQConfig::fromEnvironment();
            $this->assertIsInt($config->port);
        } finally {
            putenv('RABBITMQ_PORT');
        }
    }
}
