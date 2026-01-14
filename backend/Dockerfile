# Symfony Backend Dockerfile
FROM php:8.3-fpm-alpine AS base

# Arguments pour les permissions
ARG USER_ID=1000
ARG GROUP_ID=1000

# Installation des dépendances système
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    mysql-client \
    linux-headers \
    $PHPIZE_DEPS

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
        intl \
        opcache \
        gd \
        bcmath \
        mbstring

# Installation de Xdebug (pour le développement)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /var/run/nginx

# Configuration Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Création de l'utilisateur avec les mêmes UID/GID que l'hôte
RUN addgroup -g ${GROUP_ID} appgroup || true \
    && adduser -D -u ${USER_ID} -G appgroup appuser || true

# Répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY --chown=appuser:appgroup . .

# Installation des dépendances Composer
RUN composer install --no-interaction --no-scripts --prefer-dist

# Permissions
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/config/jwt \
    && chown -R appuser:appgroup /var/www/html \
    && chmod -R 775 /var/www/html/var \
    && chown -R appuser:appgroup /var/www/html/config/jwt

# Exposition du port
EXPOSE 80

# Démarrage avec Supervisor (Nginx + PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
