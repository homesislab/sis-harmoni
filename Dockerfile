FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    git zip unzip \
    libpng-dev libonig-dev libxml2-dev libicu-dev \
  && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd intl \
  && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Composer + install deps (dotenv)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# CI3 writable dirs
RUN mkdir -p application/cache application/logs \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 application/cache application/logs

EXPOSE 80
