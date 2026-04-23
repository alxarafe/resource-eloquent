FROM php:8.2-cli

# Install system dependencies for Composer and extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
