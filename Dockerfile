FROM docker.io/library/composer:2 AS composer_deps

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-reqs
COPY . /app
RUN composer dump-autoload --no-dev --optimize --no-interaction

FROM docker.io/leantime/leantime:latest

USER root
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html
COPY --from=composer_deps /app/vendor /var/www/html/vendor
RUN mkdir -p /var/www/html/bootstrap/cache     && chown -R www-data:www-data /var/www/html/bootstrap /var/www/html/storage /var/www/html/vendor     && chmod 775 /var/www/html/bootstrap /var/www/html/bootstrap/cache
USER www-data
