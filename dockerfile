FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    openssl \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql opcache zip mbstring

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies (no scripts — no DB/cache needed at build time)
COPY composer.json composer.lock symfony.lock ./
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN cp docker/.env.docker .env \
    && composer dump-autoload --optimize --classmap-authoritative --no-interaction \
    && mkdir -p var/cache var/log config/jwt public/bundles \
    && chmod +x scripts/railway-start.sh

ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 8000

CMD ["sh", "scripts/railway-start.sh"]
