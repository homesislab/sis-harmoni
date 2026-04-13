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

# CI3 writable dirs only. Keep source/vendor ownership unchanged so Docker
# builds don't spend ages chown-ing the whole application tree.
RUN mkdir -p application/cache application/logs uploads \
  && chown -R www-data:www-data application/cache application/logs uploads \
  && chmod -R 775 application/cache application/logs uploads

EXPOSE 80
