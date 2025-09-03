# syntax=docker/dockerfile:1
# Use PHP 8.2 FPM as base image
FROM php:8.2-fpm

ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production

# System deps
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    nodejs \
    npm \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www

# Copia (en desarrollo será sobrescrita por el bind mount de docker-compose)
COPY . /var/www

# Instala dependencias PHP SIN scripts (evita correr Artisan en build)
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts || true

# Build de front solo si hay package.json
RUN [ -f package.json ] && npm install && npm run build || true

# Permisos
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# PHP-FPM
EXPOSE 9000
CMD ["php-fpm"]
