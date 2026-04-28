# syntax=docker/dockerfile:1

FROM php:8.5-cli-bookworm AS app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        bison \
        ca-certificates \
        curl \
        git \
        libtool \
        libpq-dev \
        postgresql-client \
        unzip \
    && docker-php-ext-install pdo_pgsql pgsql \
    && curl -fL --output /usr/local/bin/pie https://github.com/php/pie/releases/latest/download/pie.phar \
    && chmod +x /usr/local/bin/pie \
    && pie install php-ds/ext-ds \
    && php -m | grep -i '^ds$' \
    && rm -rf /var/lib/apt/lists/* /tmp/pear ~/.cache/pie

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-progress

FROM dunglas/frankenphp:php8.5-bookworm AS frankenphp-dev

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    XDEBUG_MODE=debug,develop \
    XDEBUG_START_WITH_REQUEST=yes \
    XDEBUG_CLIENT_HOST=host.docker.internal \
    XDEBUG_CLIENT_PORT=9003 \
    XDEBUG_IDE_KEY=PHPSTORM \
    PHP_IDE_CONFIG=serverName=addendum-frankenphp

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        bison \
        ca-certificates \
        curl \
        git \
        libtool \
        libpq-dev \
        postgresql-client \
        unzip \
    && docker-php-ext-install pdo_pgsql pgsql \
    && curl -fL --output /usr/local/bin/pie https://github.com/php/pie/releases/latest/download/pie.phar \
    && chmod +x /usr/local/bin/pie \
    && pie install php-ds/ext-ds \
    && pie install xdebug/xdebug \
    && php -m | grep -i '^ds$' \
    && php -m | grep -i '^xdebug$' \
    && rm -rf /var/lib/apt/lists/* /tmp/pear ~/.cache/pie

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY dev/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY dev/frankenphp/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

WORKDIR /app

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-progress

FROM postgres:17-bookworm AS postgres-pgtap

RUN apt-get update \
    && apt-get install -y --no-install-recommends postgresql-17-pgtap \
    && rm -rf /var/lib/apt/lists/*
