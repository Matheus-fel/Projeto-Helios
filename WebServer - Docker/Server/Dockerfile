FROM php:8.2-apache

# Dependências de sistema para extensões PHP e Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip \
    default-mysql-client \
    libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev \
  && rm -rf /var/lib/apt/lists/*

# Extensões PHP comuns (e úteis para PDF/imagens) + otimizações
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
    mysqli pdo pdo_mysql \
    zip \
    gd \
    intl \
    opcache

# Apache: rewrite + headers (muito usado em frameworks)
RUN a2enmod rewrite headers \
  && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permite .htaccess funcionar (para rewrite)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf


# Copia o restante do código (em PROD). Em DEV isso é sobrescrito pelo bind mount.
COPY ./www/ /var/www/html/

# Se seu projeto NÃO usa /public, remova o APACHE_DOCUMENT_ROOT no compose
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

EXPOSE 80

