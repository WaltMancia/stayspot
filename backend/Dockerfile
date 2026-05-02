# ── Dependencias ─────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-configure gd \
        --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql mbstring gd zip intl opcache bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Build ─────────────────────────────────────────────────────
FROM base AS builder
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-dev

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ── Producción ────────────────────────────────────────────────
FROM base AS production
WORKDIR /var/www/html

COPY --from=builder /app .

COPY docker/nginx.conf      /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh   /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]