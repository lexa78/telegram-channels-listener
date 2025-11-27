<?php

namespace App\Classes;

use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMQWrapper
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $pass,
        private readonly string $queue,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Подключение к RabbitMQ и объявление очереди
     */
    private function connect(): bool
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->pass
            );

            $this->channel = $this->connection->channel();
            $this->channel->queue_declare($this->queue, false, true, false, false);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Ошибка подключения к RabbitMQ. Описание:'.$e->getMessage());
            $this->connection = null;
            $this->channel = null;

            return false;
        }
    }

    /**
     * Проверка активности подключения. Если оборвано, переподключаемся
     */
    private function ensureConnection(): bool
    {
        if ($this->connection instanceof AMQPStreamConnection && $this->connection->isConnected()) {
            return true;
        }

        return $this->connect();
    }

    /**
     * Отправка сообщения в очередь
     */
    public function publish(string $message): bool
    {
        if (!$this->ensureConnection()) {
            // RabbitMQ недоступен — логируем, но НЕ падаем.
            $this->logger->info('ensureConnection() returned false');

            return false;
        }

        try {
            $msg = new AMQPMessage($message, [
                'delivery_mode' => 2  // <-- persistent message
            ]);
            $this->channel->basic_publish($msg, '', $this->queue);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Ошибка при отправке сообщения в очередь. Описание:'.$e->getMessage());

            // Попытка переподключения
            if (!$this->connect()) {
                $this->logger->info('Попытка переподключения не удалась');

                return false;
            }

            try {
                $msg = new AMQPMessage($message);
                $this->channel->basic_publish($msg, '', $this->queue);

                return true;
            } catch (Throwable $e) {
                // И вторая попытка не удалась
                $this->logger->error('Ошибка при повторной отправке сообщения в очередь. Описание:'.$e->getMessage());

                return false;
            }
        }
    }

    /**
     * Закрытие соединений
     */
    public function close(): void
    {
        if ($this->channel === null && $this->connection === null) {
            return;
        }

        if ($this->channel !== null) {
            try {
                $this->channel->close();
            } catch (Throwable $e) {
                $this->logger->error('Ошибка при закрытии канала в RabbitMQ. Описание:'.$e->getMessage());
            }
        }

        if ($this->connection !== null) {
            try {
                $this->connection->close();
            } catch (Throwable $e) {
                $this->logger->error('Ошибка при закрытии соединения в RabbitMQ. Описание:'.$e->getMessage());
            }
        }
    }
}
