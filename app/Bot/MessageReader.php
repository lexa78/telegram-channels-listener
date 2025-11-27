<?php

declare(strict_types=1);

namespace App\Bot;

use App\Classes\RabbitMQWrapper;
use danog\MadelineProto\EventHandler;
use Monolog\Logger;

class MessageReader extends EventHandler
{
    // т.к. у EventHandler конструктор final private, то костыляем
    private static Logger $mainLogger;

    private static Logger $subsidiaryLogger;

    private static RabbitMQWrapper $rabbit;

    /**
     * Что-то типа конструктора
     */
    public static function init(RabbitMQWrapper $rabbit, Logger $mainLogger, Logger $subsidiaryLogger): void
    {
        self::$mainLogger = $mainLogger;
        self::$subsidiaryLogger = $subsidiaryLogger;
        self::$rabbit = $rabbit;
    }

    /**
     * Обработка полученных сообщений.
     * Метод получает информацию о канале (id, name)) и отправляет сырое сообщение из канала с этими данными в RabbitMQ
     */
    public function onUpdateNewChannelMessage(array $update): void
    {
        // Получаем ID канала
        $channelId = $update['message']['peer_id'] ?? null;
        if ($channelId === null) {
            $title = 'Сообщение без channel_id';
            $channelId = 'null';
        } else {
            // Получаем информацию о канале
            $info = $this->getInfo($channelId);
            $title = $info['Chat']['title'] ?? 'Unknown channel';
        }

        $message = [
            'channelTitle' => $title,
            'channelId' => $channelId,
            'data' => $update,
        ];
        $isPublished = self::$rabbit->publish(json_encode($message));
        if (!$isPublished) {
            $unsentMessage = '['.$title.'] ('.$channelId.'): '.json_encode($update).PHP_EOL;
            self::$subsidiaryLogger->info($unsentMessage);
        }
    }

    /**
     * Закрытие всех соединений RabbitMQ при остановке бота
     */
    public function onStop(): void
    {
        self::$rabbit->close();
    }
}
