# 🐳 Configuration Docker

Ce dossier contient toutes les configurations Docker nécessaires pour déployer l'application sur Render.

## 📁 Structure

```
docker/
├── nginx/
│   ├── nginx.conf          # Configuration principale Nginx
│   └── default.conf        # Configuration du serveur virtuel
├── php/
│   ├── php.ini             # Configuration PHP pour production
│   └── opcache.ini         # Configuration OPcache + JIT
├── supervisor/
│   └── supervisord.conf    # Configuration Supervisor (PHP-FPM + Nginx)
├── start.sh                # Script de démarrage de l'application
└── README.md               # Ce fichier
```

## 🔧 Configurations

### Nginx
- **Port d'écoute** : 8080 (requis par Render)
- **Root** : `/var/www/html/public`
- **PHP-FPM** : 127.0.0.1:9000
- **Gzip** : Activé pour les assets statiques
- **Security Headers** : X-Frame-Options, X-Content-Type-Options, etc.
- **Health Check** : `/health` endpoint

### PHP
- **Version** : 8.4-fpm-alpine
- **Memory Limit** : 512M
- **Max Execution Time** : 300s
- **Upload Max Size** : 50M
- **OPcache** : Activé avec JIT
- **Extensions** : PDO, MySQL, PostgreSQL, GD, Zip, etc.

### Supervisor
- **PHP-FPM** : Priority 5
- **Nginx** : Priority 10
- **Workers Laravel** : Commentés (à activer si besoin)
- **Scheduler Laravel** : Commenté (à activer si besoin)

## 🚀 Tester Localement

### Build de l'image
```bash
docker build -t interventio-api .
```

### Run du container
```bash
docker run -p 8080:8080 \
  -e APP_KEY=base64:xxxxx \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=5432 \
  -e DB_DATABASE=interventio \
  -e DB_USERNAME=user \
  -e DB_PASSWORD=password \
  interventio-api
```

### Accéder à l'application
```bash
open http://localhost:8080
curl http://localhost:8080/health
```

## 📝 Notes

### OPcache
- `validate_timestamps=0` : Pas de vérification des timestamps en production
- JIT activé pour PHP 8.4 : Performance accrue

### Security
- Utilisateur non-root (`www`)
- Fichiers sensibles bloqués par Nginx
- Headers de sécurité ajoutés

### Logs
- **Nginx** : `/var/log/nginx/`
- **PHP-FPM** : stdout/stderr
- **Laravel** : `/var/www/html/storage/logs/`

## 🔄 Mise à Jour

### Modifier la configuration Nginx
1. Éditer `nginx/default.conf`
2. Rebuild l'image Docker
3. Redéployer sur Render (git push)

### Modifier la configuration PHP
1. Éditer `php/php.ini` ou `php/opcache.ini`
2. Rebuild l'image Docker
3. Redéployer sur Render

### Activer les Workers/Scheduler
1. Décommenter les sections dans `supervisor/supervisord.conf`
2. Rebuild l'image Docker
3. Redéployer sur Render

## 🐛 Débogage

### Logs en temps réel
```bash
# Dans le container
tail -f /var/log/nginx/error.log
tail -f /var/www/html/storage/logs/laravel.log
```

### Vérifier la configuration Nginx
```bash
nginx -t
```

### Vérifier la configuration PHP
```bash
php -i | grep opcache
php -v
```

### Vérifier Supervisor
```bash
supervisorctl status
```

## 📚 Ressources

- [Documentation PHP-FPM](https://www.php.net/manual/fr/install.fpm.php)
- [Documentation Nginx](https://nginx.org/en/docs/)
- [Documentation Supervisor](http://supervisord.org/)
- [PHP OPcache](https://www.php.net/manual/fr/book.opcache.php)

