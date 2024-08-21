FROM php:8.3-fpm-alpine

LABEL authors="GeoffreyBauer"

RUN apk add autoconf g++ make curl-dev pkgconfig

RUN docker-php-ext-install pdo

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/symfo