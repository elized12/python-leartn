FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

FROM node:22-bookworm-slim AS frontend

WORKDIR /app
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_APP_NAME="${VITE_APP_NAME}" \
    VITE_REVERB_APP_KEY="${VITE_REVERB_APP_KEY}" \
    VITE_REVERB_HOST="${VITE_REVERB_HOST}" \
    VITE_REVERB_PORT="${VITE_REVERB_PORT}" \
    VITE_REVERB_SCHEME="${VITE_REVERB_SCHEME}"
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN rm -f public/hot && npm run build

FROM php:8.3-fpm-bookworm AS app

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        docker.io \
        git \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install pdo_mysql pcntl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY docker/deploy/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/deploy/queue-worker.sh /usr/local/bin/app-queue-worker
COPY docker/deploy/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN rm -f public/hot \
    && rm -f public/storage \
    && ln -s ../storage/app/public public/storage \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && chmod +x /usr/local/bin/app-entrypoint \
    && chmod +x /usr/local/bin/app-queue-worker \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx

WORKDIR /var/www/html
COPY --from=app /var/www/html/public ./public
COPY docker/deploy/nginx.conf /etc/nginx/conf.d/default.conf
