# syntax=docker/dockerfile:1

# ---- Stage 1: compile front-end assets ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources resources
COPY public public
RUN npm run build

# ---- Stage 2: application image ----
FROM php:8.2-cli-alpine AS app

RUN apk add --no-cache sqlite-libs \
    && apk add --no-cache --virtual .build-deps sqlite-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# Install PHP deps first so this layer is cached when only app code changes.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# App code (the built-in server serves only from public/, so nothing outside
# it — .env, composer.json, storage/logs, this Dockerfile — is ever web
# reachable regardless of what ships in the image).
COPY . .
COPY --from=assets /app/public/build public/build

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["entrypoint.sh"]
