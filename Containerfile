FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy app source
COPY . .

# Set correct permissions for Laravel storage
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose php-fpm port
EXPOSE 9000

CMD ["php-fpm"]

