FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache \
        fcgi \
        libcurl \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
        oniguruma \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        curl-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath curl gd intl opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

FROM php-base AS builder

RUN apk add --no-cache git nodejs npm unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY package.json ./
RUN npm install --include=dev --no-audit --no-fund

COPY . .

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && php artisan wayfinder:generate --with-form \
    && npm run build \
    && rm -rf node_modules

FROM php-base AS app

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm", "-F"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=builder /var/www/html/public /var/www/html/public

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1
