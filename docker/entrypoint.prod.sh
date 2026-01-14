#!/bin/bash

# Create log directory for PHP
mkdir -p /var/log/php

# Fix files ownership
chown -R www-data:www-data /app/storage
chown -R www-data:www-data /app/bootstrap/cache

# Set correct permissions
chmod -R 775 /app/storage
chmod -R 775 /app/bootstrap/cache

# Run any pending migrations (uncomment if you want auto-migration on deploy)
# php artisan migrate --force

# Cache configuration for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"
