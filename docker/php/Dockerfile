FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Copy and set entrypoint script to adjust permissions at container start
COPY docker/php/docker-php-entrypoint.sh /usr/local/bin/docker-php-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-php-entrypoint.sh

# Set light permissions for storage and bootstrap/cache (will be re-applied at runtime by entrypoint)
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 and start php-fpm server
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint.sh"]
CMD ["php-fpm"]
