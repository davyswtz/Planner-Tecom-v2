#!/bin/sh
set -e

cd /var/www

php artisan package:discover --ansi --no-interaction 2>/dev/null || true

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    if [ -f .env ] && grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        :
    else
        php artisan key:generate --force --no-interaction 2>/dev/null || true
    fi
fi

php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

mkdir -p storage/app/public

exec "$@"
