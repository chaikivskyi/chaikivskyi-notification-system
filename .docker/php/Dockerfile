FROM php:8.5-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    linux-headers \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    icu-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    su-exec

# Install PHP extensions
RUN docker-php-ext-install sockets pdo pdo_mysql mbstring intl zip pcntl

# Download PIE
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers zstd-dev lz4-dev liblzf-dev; \
    curl -fsSL -o /usr/local/bin/pie https://github.com/php/pie/releases/download/1.4.4/pie.phar; \
    chmod +x /usr/local/bin/pie;

RUN pie install phpredis/phpredis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

CMD ["php-fpm"]
