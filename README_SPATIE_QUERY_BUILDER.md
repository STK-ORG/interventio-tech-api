# 🎯 Spatie Query Builder - Guide Rapide

## ✅ Implémenté

Tous les endpoints utilisateur supportent maintenant l'inclusion flexible des relations via le paramètre `?include=`.

## 📚 Documentation

- **Guide Complet** : `docs/API_INCLUDES.md`
- **Exemples Pratiques** : `docs/EXAMPLES.md`
- **Swagger UI** : http://localhost/api/documentation

## 🚀 Utilisation Rapide

```bash
# Minimal (juste l'utilisateur)
GET /api/v1/users/me

# Avec profil professionnel
GET /api/v1/users/me?include=professionalProfile

# Avec plusieurs relations
GET /api/v1/users/me?include=professionalProfile,companies,posts
```

## 🎯 Relations Disponibles

- `professionalProfile` - Profil professionnel
- `companies` - Liste des entreprises
- `posts` - Liste des publications

## 📖 Lire Plus

Consultez `docs/API_INCLUDES.md` pour la documentation complète avec exemples et best practices !
