#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -S /var/run/docker.sock ]; then
    DOCKER_GID="$(stat -c '%g' /var/run/docker.sock 2>/dev/null || true)"
    if [ -n "$DOCKER_GID" ] && ! getent group "$DOCKER_GID" >/dev/null 2>&1; then
        groupadd -g "$DOCKER_GID" dockerhost >/dev/null 2>&1 || true
    fi
    if [ -n "$DOCKER_GID" ]; then
        DOCKER_GROUP="$(getent group "$DOCKER_GID" | cut -d: -f1)"
        usermod -aG "$DOCKER_GROUP" www-data >/dev/null 2>&1 || true
    fi
fi

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
rm -f public/hot

php artisan package:discover --ansi

if [ "${APP_RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${APP_OPTIMIZE:-true}" = "true" ]; then
    php artisan config:cache
    php artisan route:cache
fi

exec "$@"
