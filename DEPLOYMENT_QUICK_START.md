# ⚡ Quick Start - Déploiement sur Render

Guide rapide en 5 minutes pour déployer l'application sur Render.

---

## 🚀 Déploiement Automatique (Recommandé)

### 1. Push sur GitHub
```bash
git add .
git commit -m "Add Render deployment"
git push origin main
```

### 2. Créer sur Render
1. Aller sur [render.com](https://dashboard.render.com)
2. Cliquer sur **"New" → "Blueprint"**
3. Connecter le repo GitHub
4. Cliquer sur **"Apply"**

### 3. Configurer les Variables
Dans **Environment**, ajouter :
```bash
APP_URL=https://votre-app.onrender.com
SANCTUM_STATEFUL_DOMAINS=votre-frontend.com
SESSION_DOMAIN=.votre-domaine.com
```

### 4. ✅ C'est Prêt !
Attendre 5-10 minutes, puis :
```bash
curl https://votre-app.onrender.com/health
```

---

## 📋 Checklist Rapide

- [ ] Code sur GitHub ✅
- [ ] Compte Render créé ✅
- [ ] Blueprint appliqué ✅
- [ ] Variables d'environnement ajoutées ✅
- [ ] Domaine custom ajouté (optionnel)
- [ ] Tests API effectués ✅

---

## 🔗 Liens Utiles

- **Dashboard Render** : https://dashboard.render.com
- **Documentation Complète** : [docs/DEPLOYMENT_RENDER.md](docs/DEPLOYMENT_RENDER.md)
- **Configuration Docker** : [docker/README.md](docker/README.md)

---

## 🛠️ Test Local (Optionnel)

```bash
# Build & test Docker
./docker/test-build.sh

# Ou manuellement
docker build -t interventio-api .
docker run -p 8080:8080 interventio-api
```

---

## 🐛 Problèmes Courants

### ❌ Erreur 500
- Vérifier `APP_KEY` est généré
- Vérifier `DB_*` credentials
- Voir les logs : Dashboard → Logs

### ❌ Base de données inaccessible
- Vérifier PostgreSQL est créée
- Vérifier les variables d'environnement
- Tester : `php artisan migrate --pretend` (Shell)

### ❌ Storage non persistant
- Vérifier le disque est ajouté
- Mount path : `/var/www/html/storage`
- Size : 1 GB minimum

---

## 📞 Support

- [Documentation Render](https://render.com/docs)
- [Support Render](https://render.com/support)
- [Documentation Complète](docs/DEPLOYMENT_RENDER.md)

---

**🎉 Happy Deploying!**

