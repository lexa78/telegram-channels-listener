<?php

declare(strict_types=1);

use App\Bot\BotRunner;

$container = require __DIR__.'/bootstrap.php';

// Стартуем Telegram бота
BotRunner::run(
    session: 'session.madeline',
    settings: $container['settings'],
    rabbit: $container['rabbit'],
    mainLogger: $container['mainLogger'],
    subsidiaryLogger: $container['subsidiaryLogger'],
);
