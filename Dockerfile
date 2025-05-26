FROM php:8.2-apache

# Install SQLite3 extension
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && docker-php-ext-install sqlite3

# Copy source code
COPY ./src /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html