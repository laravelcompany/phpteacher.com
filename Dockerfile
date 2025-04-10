# Main application image
FROM php:8.3

# Set build-time arguments
ARG PROXY_SERVER_PORT
ARG PROXY_LIST_URL
ARG WEBSITES
ARG DATABASE_PATH
ARG USER_ID=1000
ARG GROUP_ID=1001
ARG USERNAME=saturn

# Install essential system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl ca-certificates git supervisor sqlite3 libzip-dev libpq-dev redis-server \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install exif zip pdo_mysql pcntl sockets && \
    docker-php-ext-enable exif zip pcntl sockets

# Install Redis PHP extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# Install Composer
RUN curl -sSL https://getcomposer.org/download/latest-stable/composer.phar -o /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer

# Set up application
# Set up application
WORKDIR /var/www
COPY . .

# Install PHP dependencies with verbose output
RUN cd /var/www && \
    ls -la && \
    composer install --no-interaction --no-suggest -v && \
    ls -la vendor
# Configure Redis
RUN mkdir -p /var/lib/redis /var/log/redis
COPY . .
# Configure Supervisor
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf


# Expose ports
EXPOSE 13000 13001 13002 6379

# Set working directory
WORKDIR /var/www/

# Start Supervisor
ENTRYPOINT ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
