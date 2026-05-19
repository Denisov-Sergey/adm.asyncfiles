<?php

declare(strict_types=1);

namespace Adm\Asyncfiles\Service;

use Adm\Asyncfiles\Config\RabbitMQConfig;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

/**
 * Сервис для публикации задач и подписки на очередь RabbitMQ.
 */
class RabbitMQService
{
    /** @var AMQPStreamConnection Соединение с RabbitMQ */
    private AMQPStreamConnection $connection;

    /** @var AMQPChannel Канал обмена сообщениями */
    private AMQPChannel $channel;

    /** @var string Имя очереди */
    private string $queueName;

    /**
     * @param RabbitMQConfig $config Конфигурация подключения
     * @throws Exception
     */
    public function __construct(RabbitMQConfig $config)
    {
        $this->queueName = $config->queueName;
        $this->connection = new AMQPStreamConnection(
            $config->host,
            $config->port,
            $config->user,
            $config->password
        );
        $this->channel = $this->connection->channel();

        // durable = true — очередь переживёт перезапуск RabbitMQ
        $this->channel->queue_declare(
            $this->queueName,
            false,  // passive
            true,   // durable
            false,  // exclusive
            false   // auto_delete
        );
    }

    /**
     * Публикует сообщение в очередь (persistent).
     *
     * @param array $data Данные задачи
     */
    public function publish(array $data): void
    {
        $messageBody = json_encode($data, JSON_UNESCAPED_UNICODE);
        $message = new AMQPMessage(
            $messageBody,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channel->basic_publish($message, '', $this->queueName);
    }

    /**
     * Подписка на очередь. prefetch_count=1 для fair dispatch.
     *
     * @param callable $callback Обработчик сообщений
     */
    public function consume(callable $callback): void
    {
        $this->channel->basic_qos(0, 1, false);
        $this->channel->basic_consume(
            $this->queueName,
            '',     // consumer_tag
            false,  // no_local
            false,  // no_ack (ручное подтверждение)
            false,  // exclusive
            false,  // nowait
            $callback
        );

        while ($this->channel->is_open()) {
            $this->channel->wait();
        }
    }

    /**
     * Закрывает соединение с RabbitMQ.
     */
    public function __destruct()
    {
        try {
            if (isset($this->channel) && $this->channel->is_open()) {
                $this->channel->close();
            }
            if (isset($this->connection) && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (Exception) {
        }
    }
}
