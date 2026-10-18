FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql

# Un único MPM (prefork, el que necesita mod_php); Apache falla si hay más de uno cargado.
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache escucha en $PORT (Railway lo inyecta) y sirve public/ con front controller.
COPY docker/ports.conf /etc/apache2/ports.conf
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-cafepunto.ini

WORKDIR /var/www/html

# Primero las dependencias, para aprovechar la caché de capas.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && chmod +x bin/entrypoint.sh

ENV PORT=8080 APP_ENV=production
EXPOSE 8080

# Migra la base de datos (y siembra datos demo si SEED_DEMO=true) y arranca Apache.
CMD ["bin/entrypoint.sh"]
