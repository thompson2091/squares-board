# =============================================================================
# BASE IMAGE - Local Development
# =============================================================================
FROM php:8.5-fpm AS php_base_local

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Set working directory
WORKDIR /app

# Copying composer.lock and composer.json (if they exist)
COPY composer.lock* composer.json* /app/

# Copying package.json and package-lock.json (if they exist)
COPY package.json* package-lock.json* /app/

# =============================================================================
# LOCAL DEVELOPMENT TARGET
# =============================================================================
FROM php_base_local AS php_local

# Install Xdebug for debugging
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy entrypoint file
COPY ./docker/entrypoint.sh /usr/local/bin/

# Make the entrypoint file executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Install dev composer dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then composer install --prefer-dist --no-scripts --no-progress --no-interaction; fi

# Install dev npm dependencies (if package.json exists)
RUN if [ -f package.json ]; then npm ci || npm install; fi

# Copying application code (changes more frequently)
COPY --chown=www-data:www-data . /app

# Create laravel caching folders
RUN mkdir -p ./storage/framework/cache \
    && mkdir -p ./storage/framework/sessions \
    && mkdir -p ./storage/framework/views \
    && mkdir -p ./storage/framework/testing \
    && mkdir -p ./bootstrap/cache

# Adjust user permission & group
RUN usermod --uid 1000 www-data && groupmod --gid 1000 www-data

# Finish composer (if composer.json exists)
RUN if [ -f composer.json ]; then composer dump-autoload --optimize; fi

# Run the entrypoint file
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

# =============================================================================
# BASE IMAGE - Production (for AWS ECS)
# =============================================================================
FROM php:8.5-fpm AS php_base_prod

# Install system dependencies (production - minimal)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Set working directory
WORKDIR /app

# Copying composer.lock and composer.json
COPY composer.lock composer.json /app/

# Copying package.json and package-lock.json
COPY package.json package-lock.json /app/

# =============================================================================
# BETA/STAGING TARGET
# =============================================================================
FROM php_base_prod AS php_beta

# Copy entrypoint file
COPY ./docker/entrypoint.prod.sh /usr/local/bin/

# Make the entrypoint file executable
RUN chmod +x /usr/local/bin/entrypoint.prod.sh

# Install prod composer dependencies (no dev)
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction --no-dev

# Install prod npm dependencies
RUN npm ci

# Copying application code (changes more frequently)
COPY --chown=www-data:www-data . /app

# Create laravel caching folders
RUN mkdir -p ./storage/framework/cache \
    && mkdir -p ./storage/framework/sessions \
    && mkdir -p ./storage/framework/views \
    && mkdir -p ./storage/framework/testing \
    && mkdir -p ./bootstrap/cache

# Adjust user permission & group
RUN usermod --uid 1000 www-data && groupmod --gid 1000 www-data

# Finish composer
RUN composer dump-autoload --optimize

# Build production assets
RUN npm run build || npm run production || true

# Ensure proper file ownership and permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap

# Run the entrypoint file
ENTRYPOINT [ "/usr/local/bin/entrypoint.prod.sh" ]

# =============================================================================
# PRODUCTION TARGET
# =============================================================================
FROM php_beta AS php_prod
