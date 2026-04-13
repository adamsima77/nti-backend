FROM php:8.3-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libpq-dev libzip-dev libxml2-dev libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip xml bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
