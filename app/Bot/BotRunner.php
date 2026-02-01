<?php

declare(strict_types=1);

namespace App\Bot;

use App\Classes\RabbitMQWrapper;
use danog\MadelineProto\Settings;
use Monolog\Logger;

class BotRunner
{
    /**
     * Запуск слушателя каналов
     */
    public static function run(
        string $session,
        Settings $settings,
        RabbitMQWrapper $rabbit,
        Logger $mainLogger,
        Logger $subsidiaryLogger,
    ): void {
        // Инициализируем EventHandler
        MessageReader::init($rabbit, $mainLogger, $subsidiaryLogger);

        // Запуск EventHandler
        MessageReader::startAndLoop($session, $settings);
    }
}
