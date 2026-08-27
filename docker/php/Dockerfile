FROM php:8.2-fpm

RUN docker-php-ext-install pdo_mysql

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
