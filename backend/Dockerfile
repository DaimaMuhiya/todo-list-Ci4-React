# Image PHP/Apache pour déploiement Render (Web Service → runtime Docker).
# Répertoire racine web : public/ (CodeIgniter 4).
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl mbstring pdo_mysql opcache zip curl \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && composer clear-cache \
    && rm -f /usr/bin/composer \
    && chown -R www-data:www-data /var/www/html/writable

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
