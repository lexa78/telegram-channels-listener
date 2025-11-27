FROM php:8.2-cli

# Установка расширений: curl, mbstring, openssl — нужно MadelineProto
RUN apt-get update && apt-get install -y \
    git unzip libssl-dev libcurl4-openssl-dev \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install sockets \
    && docker-php-ext-install bcmath

WORKDIR /app

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Установка зависимостей из composer.json
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist

# Добавляем весь проект
COPY . .

# Запуск бота
CMD ["php", "index.php"]
