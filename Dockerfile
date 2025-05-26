FROM php:8.2-apache

# Install SQLite3 extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-configure pdo_sqlite \
    && docker-php-ext-configure sqlite3 \
    && docker-php-ext-install -j$(nproc) pdo_sqlite sqlite3

# Copy source code
COPY ./src /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html
