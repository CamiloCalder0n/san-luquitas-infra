# syntax=docker/dockerfile:1
# Un solo Dockerfile con dos imágenes finales:
#   target app -> PHP-FPM con el código de Laravel
#   target web -> Nginx con los archivos públicos

# 1. Dependencias de PHP (composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY portal-laravel/composer.json portal-laravel/composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader \
    --prefer-dist --ignore-platform-reqs
COPY portal-laravel/ ./
RUN composer dump-autoload --optimize --no-dev --no-scripts

# 2. Compilar CSS/JS con Vite
FROM node:22-alpine AS assets
WORKDIR /app
COPY portal-laravel/package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY portal-laravel/ ./
RUN npm run build

# 3. Imagen de la aplicación (PHP-FPM)
FROM php:8.3-fpm-alpine AS app
RUN docker-php-ext-install pdo_mysql opcache
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-portal.ini
WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app ./
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# 4. Imagen del servidor web (Nginx)
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /app/public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build
