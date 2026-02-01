<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Amp\Log\StreamHandler;
use App\Classes\RabbitMQWrapper;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use Dotenv\Dotenv;
use Monolog\Logger;
use danog\MadelineProto\Settings\AppInfo;

touch('/tmp/bot_alive');

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

$appInfo = (new AppInfo)
    ->setApiId($apiId)
    ->setApiHash($apiHash);

// Объект общих настроек
$settings = new Settings;

// Устанавливаем AppInfo в общие настройки
$settings->setAppInfo($appInfo);

// Настраиваем соединение (пинги)
// setPingInterval(30) заставит Madeline отправлять пинги, если нет обновлений
$settings->getConnection()->setPingInterval(30);

// Если нужен более агрессивный keep-alive на уровне сокета. Попробовать, если ping не поможет
// $settings->getConnection()->setTcpKeepalive(true);



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
