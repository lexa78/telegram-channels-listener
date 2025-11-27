<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Amp\Log\StreamHandler;
use App\Classes\RabbitMQWrapper;
use danog\MadelineProto\Tools;
use Dotenv\Dotenv;
use Monolog\Logger;
use danog\MadelineProto\Settings\AppInfo;

// Загружаем .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// -----------
// ЛОГИ
// -----------
$logsFolderPath = __DIR__.DIRECTORY_SEPARATOR.'logs';
if (!Amp\File\exists($logsFolderPath)) {
    Amp\File\createDirectory($logsFolderPath);
}
// Основной логгер бота
$handler = new StreamHandler(Tools::openFileAppendOnly($logsFolderPath.DIRECTORY_SEPARATOR.'bot.log'));
$mainLogger = new Logger('main');
$mainLogger->pushHandler($handler);
// Логгер неотправленных сообщений
$handler = new StreamHandler(Tools::openFileAppendOnly($logsFolderPath.DIRECTORY_SEPARATOR.'unhandledMessages.log'));
$subsidiaryLogger = new Logger('subsidiary');
$subsidiaryLogger->pushHandler($handler);

// -----------
// НАСТРОЙКИ TELEGRAM
// -----------
$apiId = (int) $_ENV['API_ID'];
$apiHash = $_ENV['API_HASH'];

$settings = (new AppInfo)
    ->setApiId($apiId)
    ->setApiHash($apiHash);

// -----------
// ИНИЦИАЛИЗАЦИЯ RABBITMQ
// -----------
$rabbit = new RabbitMQWrapper(
    host: $_ENV['RABBIT_HOST'],
    port: (int) $_ENV['RABBIT_PORT'],
    user: $_ENV['RABBIT_USER'],
    pass: $_ENV['RABBIT_PASS'],
    queue: $_ENV['RABBIT_QUEUE'],
    logger: $mainLogger,
);

// Возвращаем массив зависимостей
return [
    'mainLogger' => $mainLogger,
    'subsidiaryLogger' => $subsidiaryLogger,
    'rabbit' => $rabbit,
    'settings' => $settings,
];
