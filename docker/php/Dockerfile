FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    sqlite \
    sqlite-dev \
    su-exec \
    && docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN composer install --optimize-autoloader --no-interaction

COPY . .

# Ensure writable directories exist (no migration here)
RUN mkdir -p /var/log/app /var/www/database /var/www/logs \
    && chown -R www-data:www-data /var/log/app /var/www/database /var/www/logs /var/www

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]