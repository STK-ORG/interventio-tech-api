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

# Créer un fichier .env minimal si nécessaire (pour les commandes artisan)
if [ ! -f .env ]; then
    echo "📝 Creating minimal .env file..."
    echo "APP_ENV=${APP_ENV:-production}" > .env
    echo "APP_DEBUG=${APP_DEBUG:-false}" >> .env
fi

# Générer la clé de l'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
else
    echo "✅ Application key already set"
    # Écrire APP_KEY dans le .env pour les commandes artisan
    if grep -q "^APP_KEY=" .env 2>/dev/null; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
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

