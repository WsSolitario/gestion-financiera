# syntax=docker/dockerfile:1

# Build front-end assets with a fixed Node version
FROM node:20 AS frontend
WORKDIR /app
COPY package*.json vite.config.js ./
COPY resources ./resources
RUN npm install && npm run build

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
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www

# Copy application source
COPY . /var/www

# Copy built assets from the Node stage
COPY --from=frontend /app/public/build /var/www/public/build

# Install PHP dependencies without running scripts (avoids Artisan during build)
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts || true

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# PHP-FPM
EXPOSE 9000
CMD ["php-fpm"]

