FROM php:7.2-apache
COPY . /var/www/html/cloudcsv_api

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git \
    -y libpq-dev \
    zip \
    unzip

# PDO driver
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pdo_pgsql

#Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod rewrite

EXPOSE 80

WORKDIR /var/www/html/cloudcsv_api
RUN composer install