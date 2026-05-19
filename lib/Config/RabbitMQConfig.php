<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Config;

/**
 * Readonly DTO конфигурации подключения к RabbitMQ.
 */
final readonly class RabbitMQConfig
{
    /**
     * @param string $host Хост RabbitMQ-сервера
     * @param int $port Порт AMQP-протокола
     * @param string $user Имя пользователя
     * @param string $password Пароль
     * @param string $queueName Имя очереди для обработки файлов
     */
    public function __construct(
        public string $host = 'rabbitmq',
        public int $port = 5672,
        public string $user = 'bitrix',
        public string $password = 'bitrix',
        public string $queueName = 'file_processing_queue',
    ) {
    }

    /**
     * Создаёт конфиг из переменных окружения (RABBITMQ_*).
     */
    public static function fromEnvironment(): self
    {
        return new self(
            host: getenv('RABBITMQ_HOST') ?: 'rabbitmq',
            port: (int)(getenv('RABBITMQ_PORT') ?: 5672),
            user: getenv('RABBITMQ_USER') ?: 'bitrix',
            password: getenv('RABBITMQ_PASSWORD') ?: 'bitrix',
            queueName: getenv('RABBITMQ_QUEUE') ?: 'file_processing_queue',
        );
    }
}
