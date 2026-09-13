# syntax=docker/dockerfile:1

# ─────────────────────────────────────────────────────────────────────
# 1) Build the front-end assets (Vite → public/build)
# ─────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ─────────────────────────────────────────────────────────────────────
# 2) Application image — php-fpm + nginx in one container (serves :8080)
#    https://serversideup.net/open-source/docker-php/
# ─────────────────────────────────────────────────────────────────────
FROM serversideup/php:8.4-fpm-nginx

# Ship the populated SQLite manual; never auto-migrate over it on boot.
ENV AUTORUN_LARAVEL_MIGRATION=false \
    PHP_OPCACHE_ENABLE=1

WORKDIR /var/www/html

# Install PHP dependencies first for better layer caching.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Application source + compiled front-end assets.
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache database

# serversideup/php exposes HTTP on 8080 (non-root).
EXPOSE 8080
