FROM php:8.3-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    gettext \
    netcat-openbsd \
    default-mysql-client \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files dulu
COPY composer.json composer.lock ./

# Install dependency tanpa source app dulu
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Baru copy source code
COPY . .

# Clear cache lama
RUN rm -f bootstrap/cache/*.php

# Laravel optimize
RUN php artisan config:clear || true

# Permission
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

# Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]

CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]