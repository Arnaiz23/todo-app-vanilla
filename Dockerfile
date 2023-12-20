# Use an official PHP runtime as a parent image
FROM php:8.0-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip
RUN docker-php-ext-install zip pdo_mysql

RUN groupadd -r app && useradd -r -g app app \
    && chown -R app:app /var/www/html

USER app

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install project dependencies using Composer
RUN composer install

USER root

# Make port 80 available to the world outside this container
EXPOSE 80

# Define environment variables
ENV APACHE_DOCUMENT_ROOT /var/www/html/src

# Configure Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache modules
RUN a2enmod rewrite

# Run Apache in the foreground
CMD ["apache2-foreground"]
