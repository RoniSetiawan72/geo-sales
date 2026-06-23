FROM php:8.2-fpm

# Install dependensi sistem & ekstensi PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project Laravel
COPY . .

# Install dependencies Laravel
RUN composer install --no-interaction --optimize-autoloader

# Set permission untuk storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port (untuk php artisan serve nanti)
EXPOSE 8000

# Jalankan Laravel development server
CMD php artisan serve --host=0.0.0.0 --port=8000
