FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip default-mysql-client \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    mysqli pdo pdo_mysql zip gd intl opcache

WORKDIR /var/www/html