FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive

# 必要パッケージ：mbstring用 oniguruma (libonig-dev) / zip用 libzip-dev + zlib1g-dev / ビルドに pkg-config
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    git curl zip unzip \
    pkg-config \
    libonig-dev \
    libzip-dev zlib1g-dev; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip; \
    rm -rf /var/lib/apt/lists/*

# Composerは公式からコピーが安定
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 念のため www-data を保証（環境差対策）
RUN set -eux; \
    getent group www-data || groupadd -g 33 -r www-data; \
    id -u www-data >/dev/null 2>&1 || useradd -u 33 -r -g www-data -s /usr/sbin/nologin www-data

WORKDIR /var/www
