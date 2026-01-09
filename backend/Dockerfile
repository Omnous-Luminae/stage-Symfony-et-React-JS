# Dockerfile pour le backend Symfony
FROM php:8.2-fpm-alpine

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    unzip \
    bash \
    icu-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev

# Installation des extensions PHP nécessaires pour Symfony
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip \
    opcache \
    mysqli

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copie du fichier composer
COPY composer.json composer.lock ./

# Installation des dépendances PHP
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copie de l'application
COPY . .

# Finalisation de Composer
RUN composer dump-autoload --optimize

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html/var

# Exposition du port
EXPOSE 9000

# Commande par défaut
CMD ["php-fpm"]
