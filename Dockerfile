# Dockerfile multi-stage pour Laravel sur Render
# Stage 1: Build
FROM php:8.4-fpm-alpine AS builder

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    nginx \
    supervisor \
    nodejs \
    npm

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier les fichiers de l'application
WORKDIR /var/www/html
COPY . .

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Installation des dépendances NPM et build des assets (si nécessaire)
# RUN npm ci && npm run build

# Stage 2: Production
FROM php:8.4-fpm-alpine

# Variables d'environnement pour production
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=256
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

# Installation des dépendances runtime uniquement (pas les -dev)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    oniguruma \
    postgresql-libs \
    nginx \
    supervisor \
    bash

# Copier les extensions PHP depuis le builder (au lieu de recompiler)
COPY --from=builder /usr/local/lib/php/extensions/no-debug-non-zts-20240924/ /usr/local/lib/php/extensions/no-debug-non-zts-20240924/
COPY --from=builder /usr/local/etc/php/conf.d/docker-php-ext-*.ini /usr/local/etc/php/conf.d/

# Copier la configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copier la configuration Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copier la configuration Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /run/nginx

# Copier l'application depuis le builder
COPY --from=builder /var/www/html /var/www/html

# Copier les assets Swagger UI dans public pour qu'ils soient accessibles
RUN mkdir -p /var/www/html/public/docs/asset && \
    cp -r /var/www/html/vendor/swagger-api/swagger-ui/dist/* /var/www/html/public/docs/asset/

# Créer un utilisateur non-root
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Changer les permissions
RUN chown -R www:www /var/www/html/storage \
    && chown -R www:www /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Créer le script de démarrage
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Exposer le port
EXPOSE 8080

# Démarrer l'application
CMD ["/usr/local/bin/start.sh"]

