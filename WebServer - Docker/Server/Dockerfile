FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip default-mysql-client \
    libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev \
    libssl-dev libsasl2-dev \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    mysqli pdo pdo_mysql zip gd intl opcache

# --- Extensão MongoDB (com suporte a SSL, necessário pra autenticação SCRAM-SHA-256) ---
RUN pecl install mongodb \
  && docker-php-ext-enable mongodb

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Instala as dependências do PHP (mongodb/mongodb, etc) automaticamente no build ---
COPY ./www/public/composer.json ./www/public/composer.lock* /var/www/html/public/
RUN cd /var/www/html/public && composer install --no-interaction --no-dev --optimize-autoloader