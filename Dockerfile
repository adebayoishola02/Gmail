FROM php:8.4.1-fpm

# Install required system dependencies including GMP
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libgmp-dev && \
    rm -rf /var/lib/apt/lists/*

# Install PHP extensions including gmp
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache gmp

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Laravel code into container
COPY ./src /var/www/html

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Permissions fix for Laravel storage and bootstrap cache
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
