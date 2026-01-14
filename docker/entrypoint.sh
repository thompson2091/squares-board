#!/bin/bash

# Create log directory for PHP
mkdir -p /var/log/php

# Run any pending migrations (commented by default for safety)
# php artisan migrate --force

# Clear and cache (optional, uncomment if needed)
# php artisan optimize:clear
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"
