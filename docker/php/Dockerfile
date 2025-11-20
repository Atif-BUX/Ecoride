FROM php:8.2-apache

# Install PHP extensions required by EcoRide
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Configure Apache document root and vhost
ENV APACHE_DOCUMENT_ROOT=/var/www/html
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
