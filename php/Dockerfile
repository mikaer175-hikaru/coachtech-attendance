FROM php:8.2-fpm

# 必要なパッケージインストール
RUN apt-get update && apt-get install -y \
    zip unzip git curl libzip-dev libonig-dev libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring zip

# Composer インストール
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www
