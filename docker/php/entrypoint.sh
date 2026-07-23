#!/bin/sh
set -eu

: "${APP_KEY:?APP_KEY não configurada}"
: "${DB_PASSWORD:?DB_PASSWORD não configurada}"
: "${REDIS_PASSWORD:?REDIS_PASSWORD não configurada}"

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link --force >/dev/null 2>&1 || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
