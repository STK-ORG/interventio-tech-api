# 🚀 Guide de Déploiement sur Render avec Docker

Ce guide explique comment déployer l'application **Interventio Tech API** sur [Render](https://render.com) avec Docker.

---

## 📋 Prérequis

- Un compte Render (gratuit ou payant)
- Le code de l'application poussé sur GitHub/GitLab/Bitbucket
- Un terminal avec Git installé

---

## 🏗️ Architecture de Déploiement

```
┌─────────────────────────────────────────┐
│         Render Web Service              │
│  ┌───────────────────────────────────┐  │
│  │  Docker Container                 │  │
│  │  ┌─────────────┐  ┌─────────────┐│  │
│  │  │   Nginx     │  │  PHP-FPM    ││  │
│  │  │   (Port     │→ │  (Laravel)  ││  │
│  │  │    8080)    │  │             ││  │
│  │  └─────────────┘  └─────────────┘│  │
│  │         ↑                         │  │
│  │    Supervisor                     │  │
│  └───────────────────────────────────┘  │
│         │                                │
│    Persistent Disk (/storage)           │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────────────────────────────────┐
│    PostgreSQL Database (Render)         │
└─────────────────────────────────────────┘
```

---

## 🚀 Méthode 1 : Déploiement avec Blueprint (Recommandé)

### Étape 1 : Préparer le Repository

1. **Pousser le code sur GitHub** :
```bash
git add .
git commit -m "Add Render deployment configuration"
git push origin main
```

### Étape 2 : Créer le Service sur Render

1. Aller sur [Render Dashboard](https://dashboard.render.com/)
2. Cliquer sur **"New" → "Blueprint"**
3. Connecter votre repository GitHub/GitLab
4. Render détectera automatiquement le fichier `render.yaml`
5. Cliquer sur **"Apply"**

Render va automatiquement :
- ✅ Créer la base de données PostgreSQL
- ✅ Créer le service web
- ✅ Configurer les variables d'environnement
- ✅ Créer le disque persistant
- ✅ Déployer l'application

### Étape 3 : Configurer les Variables d'Environnement Manquantes

Dans le **Dashboard Render → Service → Environment** :

```bash
# À ajouter manuellement
APP_URL=https://votre-app.onrender.com
SANCTUM_STATEFUL_DOMAINS=votre-frontend.com
SESSION_DOMAIN=.votre-domaine.com

# Optionnel : Mail
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=votre-username
MAIL_PASSWORD=votre-password
```

---

## 🛠️ Méthode 2 : Déploiement Manuel

### Étape 1 : Créer la Base de Données

1. Dans Render Dashboard : **New → PostgreSQL**
2. Configuration :
   - **Name** : `interventio-db`
   - **Database** : `interventio`
   - **Region** : Frankfurt (ou le plus proche)
   - **Plan** : Starter (ou Free pour tester)
3. Cliquer sur **"Create Database"**
4. **Noter les credentials** (Host, Port, Database, Username, Password)

### Étape 2 : Créer le Web Service

1. Dans Render Dashboard : **New → Web Service**
2. Connecter le repository GitHub
3. Configuration :
   - **Name** : `interventio-api`
   - **Region** : Frankfurt
   - **Branch** : `main`
   - **Runtime** : Docker
   - **Plan** : Starter
   - **Dockerfile Path** : `./Dockerfile`
   - **Docker Context** : `.`
4. Cliquer sur **"Create Web Service"**

### Étape 3 : Configurer les Variables d'Environnement

Dans **Environment** :

```bash
# Application
APP_NAME=Interventio API
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-app.onrender.com

# Database (copier depuis la page PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=dpg-xxxxx.frankfurt-postgres.render.com
DB_PORT=5432
DB_DATABASE=interventio
DB_USERNAME=interventio_user
DB_PASSWORD=xxxxxxxxxxxxx

# Cache
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Sanctum
SANCTUM_EXPIRATION=30
SANCTUM_STATEFUL_DOMAINS=votre-frontend.com
SESSION_DOMAIN=.votre-domaine.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Étape 4 : Ajouter le Disque Persistant

1. Dans **Settings → Disks** : **Add Disk**
2. Configuration :
   - **Name** : `interventio-storage`
   - **Mount Path** : `/var/www/html/storage`
   - **Size** : 1 GB
3. Cliquer sur **"Save"**

### Étape 5 : Configurer le Health Check

Dans **Settings** :
- **Health Check Path** : `/health`

---

## ⚙️ Configuration Avancée

### Ajouter Redis (Recommandé pour Production)

1. **Créer un Redis sur Render** :
   - Dashboard → **New → Redis**
   - Name: `interventio-redis`
   - Plan: Starter
   - Region: Frankfurt

2. **Mettre à jour les variables d'environnement** :
```bash
REDIS_HOST=red-xxxxx.frankfurt.render.com
REDIS_PASSWORD=xxxxxxxxxxxxx
REDIS_PORT=6379

# Utiliser Redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

3. **Activer les workers dans `supervisord.conf`** :
```bash
# Décommenter les sections laravel-worker et laravel-scheduler
```

### Custom Domain

1. Dans **Settings → Custom Domains** : **Add Custom Domain**
2. Ajouter votre domaine : `api.votre-domaine.com`
3. Configurer le DNS :
   ```
   CNAME api.votre-domaine.com → interventio-api.onrender.com
   ```
4. Attendre la validation SSL (automatique)

### HTTPS & SSL

- ✅ **Render gère automatiquement le SSL** avec Let's Encrypt
- Aucune configuration nécessaire

---

## 🔍 Monitoring & Logs

### Accéder aux Logs

1. Dans le Dashboard → **Logs**
2. Logs en temps réel de :
   - Nginx
   - PHP-FPM
   - Laravel
   - Migrations

### Métriques

1. Dans **Metrics** :
   - CPU Usage
   - Memory Usage
   - Network Traffic
   - Response Times

---

## 🐛 Débogage

### Problème : Erreur 500

**Vérifier les logs** :
```bash
# Dans Render Logs
grep "ERROR" # Rechercher les erreurs
```

**Vérifier les variables d'environnement** :
- `APP_KEY` est généré ?
- `DB_*` sont corrects ?
- `APP_URL` correspond à l'URL Render ?

### Problème : Base de données inaccessible

**Vérifier la connexion** :
```bash
# Dans Shell (Render Dashboard → Shell)
php artisan migrate --pretend
```

**Vérifier les credentials** :
- Host, Port, Database, Username, Password

### Problème : Storage non persistant

**Vérifier le disque** :
- Le disque est monté sur `/var/www/html/storage` ?
- Les permissions sont correctes ?

### Shell Interactif

1. Dashboard → **Shell**
2. Exécuter des commandes :
```bash
php artisan route:list
php artisan config:show database
php artisan migrate:status
```

---

## 🔄 Redéploiement

### Automatique (Git Push)

```bash
git add .
git commit -m "Update"
git push origin main
```
→ Render redéploie automatiquement

### Manuel

1. Dashboard → **Manual Deploy → Deploy Latest Commit**

### Rollback

1. Dashboard → **Deploys**
2. Sélectionner un déploiement précédent
3. Cliquer sur **"Rollback to this deploy"**

---

## 💰 Plans & Pricing

### Plan Gratuit
- ✅ 750h/mois
- ❌ Service s'endort après 15min d'inactivité
- ❌ Temps de démarrage ~30-60s

### Plan Starter ($7/mois)
- ✅ Toujours actif
- ✅ Démarrage instantané
- ✅ 512MB RAM
- ✅ 0.5 CPU

### Plan Standard ($25/mois)
- ✅ 2GB RAM
- ✅ 1 CPU
- ✅ Autoscaling

**Recommandation** : Plan Starter minimum pour production

---

## 📊 Checklist de Déploiement

- [ ] Code poussé sur GitHub
- [ ] `render.yaml` configuré
- [ ] Base de données PostgreSQL créée
- [ ] Variables d'environnement configurées
- [ ] Disque persistant ajouté
- [ ] Health check configuré
- [ ] Domaine custom ajouté (optionnel)
- [ ] Redis ajouté (optionnel)
- [ ] Tests de l'API effectués
- [ ] Monitoring configuré
- [ ] Documentation Swagger accessible

---

## 🔗 Ressources

- [Documentation Render](https://render.com/docs)
- [Render Blueprint Spec](https://render.com/docs/blueprint-spec)
- [Render Docker](https://render.com/docs/docker)
- [Support Render](https://render.com/support)

---

## ✅ Vérification Post-Déploiement

### 1. Tester l'API
```bash
# Health check
curl https://votre-app.onrender.com/health

# API status
curl https://votre-app.onrender.com/api/v1/posts

# Documentation Swagger
open https://votre-app.onrender.com/api/documentation
```

### 2. Vérifier les Migrations
```bash
# Dans le Shell Render
php artisan migrate:status
```

### 3. Vérifier le Cache
```bash
php artisan config:show cache
php artisan route:cache
php artisan config:cache
```

### 4. Tester l'Authentification
```bash
curl -X POST https://votre-app.onrender.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "account_type": "private"
  }'
```

---

**🎉 Votre application est maintenant déployée sur Render !**

