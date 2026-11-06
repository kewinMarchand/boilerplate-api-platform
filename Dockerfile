FROM dunglas/frankenphp:1-php8.5-bookworm AS base
WORKDIR /app
RUN install-php-extensions apcu exif gd intl opcache pdo_pgsql zip \
    && chmod -R a+rwX /data/caddy /config/caddy
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY docker/uploads.ini $PHP_INI_DIR/conf.d/uploads.ini
# Images envoyées par l'admin, servies sous /media/ depuis var/media (ADR 0002)
ENV CADDY_SERVER_EXTRA_DIRECTIVES="import /app/docker/media.caddy"
ENV SERVER_NAME=:8080 \
    COMPOSER_HOME=/tmp/composer \
    HOME=/tmp
EXPOSE 8080

FROM base AS dev
ENV APP_ENV=dev
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

FROM base AS prod-deps
ENV APP_ENV=prod
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

FROM base AS prod
ENV APP_ENV=prod APP_DEBUG=0
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=prod-deps /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer dump-env prod \
    && php bin/console assets:install public \
    && php bin/console cache:warmup \
    && chown -R www-data:www-data var
USER www-data
