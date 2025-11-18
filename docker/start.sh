#!/bin/bash
set -e

echo "🚀 Starting application..."

# Attendre que la base de données soit prête (si DATABASE_URL est définie)
if [ ! -z "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database..."
    sleep 5
fi

# Créer les répertoires de stockage si nécessaire
echo "📁 Creating storage directories..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Définir les permissions
echo "🔒 Setting permissions..."
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Naviguer vers le répertoire de l'application
cd /var/www/html

# Créer un fichier .env complet depuis les variables d'environnement Render
echo "📝 Creating .env file from environment variables..."
cat > .env << EOF
# Application
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

# Database
DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-}
DB_USERNAME=${DB_USERNAME:-}
DB_PASSWORD=${DB_PASSWORD:-}

# Cache & Sessions
CACHE_STORE=${CACHE_STORE:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

# Sanctum
SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-}
SESSION_DOMAIN=${SESSION_DOMAIN:-}
SANCTUM_EXPIRATION=${SANCTUM_EXPIRATION:-30}

# Logging
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_LEVEL=${LOG_LEVEL:-error}

# Redis (if used)
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PASSWORD=${REDIS_PASSWORD:-}
REDIS_PORT=${REDIS_PORT:-6379}

# Mail (if configured)
MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@example.com}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-\${APP_NAME}}"
EOF

# Générer la clé de l'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction

    # Relire le .env pour récupérer la nouvelle clé
    export APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2-)
    echo "✅ Application key generated: ${APP_KEY:0:20}..."
else
    echo "✅ Application key already set: ${APP_KEY:0:20}..."
fi

# Exécuter les migrations
echo "🗃️ Running migrations..."
php artisan migrate --force --no-interaction

# Clear et cache des configurations
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Générer la documentation Swagger (si l5-swagger est installé)
if php artisan | grep -q "l5-swagger:generate"; then
    echo "📚 Generating Swagger documentation..."
    php artisan l5-swagger:generate
fi

# Créer le lien symbolique pour le storage (si nécessaire)
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

echo "✅ Application ready!"

# Démarrer Supervisor (qui lancera PHP-FPM et Nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

