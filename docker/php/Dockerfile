FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    git curl zip unzip \
    pkg-config \
    libonig-dev \
    libzip-dev zlib1g-dev \
    libicu-dev locales tzdata; \
    \
    # PHP拡張
    docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip intl; \
    \
    # 日本語ロケール生成（ja_JP.UTF-8）
    sed -i 's/# *ja_JP.UTF-8 UTF-8/ja_JP.UTF-8 UTF-8/' /etc/locale.gen; \
    locale-gen ja_JP.UTF-8; \
    \
    # タイムゾーンをJSTへ
    ln -snf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && echo Asia/Tokyo > /etc/timezone; \
    \
    rm -rf /var/lib/apt/lists/*

# PHPのローカル設定
RUN printf "date.timezone=Asia/Tokyo\nintl.default_locale=ja_JP\n" \
    > /usr/local/etc/php/conf.d/zz-local.ini

# Composer は公式からコピー
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# www-data を保証
RUN set -eux; \
    getent group www-data || groupadd -g 33 -r www-data; \
    id -u www-data >/dev/null 2>&1 || useradd -u 33 -r -g www-data -s /usr/sbin/nologin www-data

WORKDIR /var/www
