FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip libicu-dev libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip gd

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy the Apache configuration
COPY ./symfony/vhost.conf /etc/apache2/sites-available/000-default.conf

# Set permissions so Apache can read/write
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
