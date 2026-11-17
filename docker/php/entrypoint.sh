#!/bin/sh
# Se ejecuta cada vez que arranca el contenedor de la app.
set -e
cd /var/www/html

php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
