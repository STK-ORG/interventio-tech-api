# 🚀 API REST V1 - Documentation Complète

## 📋 Vue d'Ensemble

API REST professionnelle avec authentification, gestion multi-entreprises, publications, recherche avancée et support multilingue (FR/EN).

---

## ✨ Fonctionnalités Principales

### 🔐 **Authentification & Profils**
- ✅ Inscription (comptes privés et professionnels)
- ✅ Connexion avec tokens d'accès (30 min) et refresh tokens (30 jours)
- ✅ Déconnexion (simple et tous les appareils)
- ✅ Rafraîchissement automatique des tokens
- ✅ Gestion du profil utilisateur
- ✅ Upload/suppression d'avatar (avec conversions d'images)

### 🏢 **Gestion Multi-Entreprises**
- ✅ Création de plusieurs entreprises par utilisateur professionnel
- ✅ CRUD complet (Create, Read, Update, Delete)
- ✅ Support multilingue (descriptions FR/EN)
- ✅ Upload de logos et documents
- ✅ Système de vérification
- ✅ Soft deletes
- ✅ Filtrage et tri

### 📝 **Gestion des Publications**
- ✅ CRUD complet pour tous les utilisateurs
- ✅ Support multilingue (titres et contenus FR/EN)
- ✅ Génération automatique de slugs uniques
- ✅ Statuts (brouillon, publié, archivé)
- ✅ Upload d'image mise en avant et galerie
- ✅ Compteur de vues automatique
- ✅ **Recherche intelligente** sensible à la langue
- ✅ Filtrage et tri avancés
- ✅ Soft deletes

### 🔍 **Recherche & Filtres Avancés**
- ✅ Recherche full-text dans les publications
- ✅ Filtres multiples combinables
- ✅ Tri flexible (ASC/DESC)
- ✅ Pagination
- ✅ Inclusion flexible des relations (Spatie Query Builder)

### 🌐 **Internationalisation**
- ✅ Support FR/EN via `Accept-Language` header
- ✅ Messages d'erreur multilingues
- ✅ Labels d'énumérations traduits
- ✅ Contenu traduisible (entreprises, posts)

### 🚀 **Performance**
- ✅ Évitement des requêtes N+1 (Eager Loading)
- ✅ Inclusion flexible avec Spatie Query Builder
- ✅ Pagination sur toutes les listes
- ✅ Optimisation des requêtes SQL

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| **[Swagger UI](http://localhost/api/documentation)** | Documentation interactive complète |
| **[API_INCLUDES.md](docs/API_INCLUDES.md)** | Guide d'inclusion flexible des relations |
| **[EXAMPLES.md](docs/EXAMPLES.md)** | Exemples pratiques (React, Vue, Flutter, etc.) |
| **[COMPANIES_AND_POSTS_API.md](docs/COMPANIES_AND_POSTS_API.md)** | Guide des endpoints entreprises et publications |

---

## 🎯 Endpoints Disponibles (19 routes)

### 🔐 Authentification (5 routes)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Inscription |
| `POST` | `/api/v1/auth/login` | Connexion |
| `POST` | `/api/v1/auth/logout` | Déconnexion |
| `POST` | `/api/v1/auth/logout-all` | Déconnexion tous appareils |
| `POST` | `/api/v1/auth/refresh` | Rafraîchir le token |

### 👤 Profil Utilisateur (4 routes)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/v1/users/me` | Profil de l'utilisateur |
| `PUT` | `/api/v1/users/me` | Modifier le profil |
| `POST` | `/api/v1/users/me/avatar` | Upload avatar |
| `DELETE` | `/api/v1/users/me/avatar` | Supprimer avatar |

### 🏢 Entreprises (5 routes)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/v1/companies` | Liste des entreprises |
| `POST` | `/api/v1/companies` | Créer une entreprise |
| `GET` | `/api/v1/companies/{id}` | Détails d'une entreprise |
| `PUT` | `/api/v1/companies/{id}` | Modifier une entreprise |
| `DELETE` | `/api/v1/companies/{id}` | Supprimer une entreprise |

### 📝 Publications (5 routes)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/v1/posts` | Liste des publications |
| `POST` | `/api/v1/posts` | Créer une publication |
| `GET` | `/api/v1/posts/{slug}` | Détails d'une publication |
| `PUT` | `/api/v1/posts/{slug}` | Modifier une publication |
| `DELETE` | `/api/v1/posts/{slug}` | Supprimer une publication |

---

## 🚀 Démarrage Rapide

### 1. Installation

```bash
# Cloner le projet
git clone <repo-url>
cd interventio-tech

# Installer les dépendances
composer install

# Configuration
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate --seed
```

### 2. Lancer le Serveur

```bash
# Via Laravel Herd (recommandé)
herd link

# Ou via Artisan
php artisan serve
```

### 3. Tester l'API

#### Swagger UI (Recommandé)
```
http://localhost/api/documentation
```

#### cURL
```bash
# Inscription
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept-Language: fr" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "account_type": "professional",
    "company_name": "Tech Solutions",
    "cfe_number": "CFE-2025-001"
  }'

# Connexion
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "Password123!"
  }'

# Obtenir le profil (avec token)
curl -X GET http://localhost/api/v1/users/me?include=professionalProfile,companies \
  -H "Authorization: Bearer YOUR_TOKEN"

# Rechercher des publications
curl -X GET "http://localhost/api/v1/posts?filter[search]=Laravel&filter[status]=published&sort=-views_count" \
  -H "Accept-Language: fr"
```

---

## 🎨 Exemples d'Utilisation

### React / TypeScript

```typescript
// Service API
class ApiService {
  private baseUrl = '/api/v1';
  private token = localStorage.getItem('token');

  async getPosts(search?: string, status?: string) {
    const params = new URLSearchParams();
    if (search) params.append('filter[search]', search);
    if (status) params.append('filter[status]', status);
    params.append('include', 'user');
    params.append('sort', '-published_at');

    const response = await fetch(
      `${this.baseUrl}/posts?${params.toString()}`,
      {
        headers: {
          'Accept-Language': 'fr',
        }
      }
    );

    return response.json();
  }

  async createCompany(formData: FormData) {
    const response = await fetch(`${this.baseUrl}/companies`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept-Language': 'fr',
      },
      body: formData,
    });

    return response.json();
  }
}
```

### Flutter / Dart

```dart
class ApiService {
  final String baseUrl = 'https://api.example.com/api/v1';

  Future<List<Post>> getPosts({String? search}) async {
    final queryParams = {
      'include': 'user',
      'sort': '-published_at',
      if (search != null) 'filter[search]': search,
    };

    final uri = Uri.parse('$baseUrl/posts')
        .replace(queryParameters: queryParams);

    final response = await http.get(uri, headers: {
      'Accept-Language': 'fr',
    });

    final data = json.decode(response.body);
    return (data['data'] as List)
        .map((post) => Post.fromJson(post))
        .toList();
  }
}
```

---

## 🔒 Sécurité

### Authentication
- ✅ Laravel Sanctum avec tokens bearer
- ✅ Access tokens courts (30 min)
- ✅ Refresh tokens longs (30 jours)
- ✅ Révocation de tokens par appareil

### Authorization
- ✅ Laravel Policies pour les autorisations
- ✅ Seuls les propriétaires peuvent modifier leurs ressources
- ✅ Comptes professionnels requis pour les entreprises

### Validation
- ✅ Form Requests pour toute validation
- ✅ Messages d'erreur multilingues
- ✅ Validation stricte des uploads

---

## 📊 Performance & Optimisation

| Fonctionnalité | Implementation |
|----------------|----------------|
| **N+1 Queries** | ✅ Eager Loading automatique |
| **Inclusion Flexible** | ✅ Spatie Query Builder |
| **Pagination** | ✅ 15 résultats par défaut |
| **Filtres** | ✅ Optimisés avec index DB |
| **Recherche** | ✅ Full-text avec JSON |
| **Images** | ✅ Conversions multiples (thumb, medium) |

---

## 🧪 Tests

### Lancer les Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=AuthTest
php artisan test tests/Feature/PostTest.php

# Avec coverage
php artisan test --coverage
```

### Linting & Static Analysis

```bash
# Laravel Pint (code style)
composer pint

# PHPStan (static analysis)
composer stan

# Tout vérifier
composer check
```

---

## 📦 Stack Technique

- **Framework** : Laravel 12
- **PHP** : 8.4
- **Authentication** : Laravel Sanctum v4
- **API Resources** : Laravel Eloquent Resources
- **Query Builder** : Spatie Laravel Query Builder
- **Media** : Spatie Laravel MediaLibrary
- **Translations** : Spatie Laravel Translatable
- **Documentation** : Swagger (darkaonline/l5-swagger)
- **Code Style** : Laravel Pint (PER preset)
- **Static Analysis** : Larastan (PHPStan) Level 7
- **Testing** : Pest v4

---

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/amazing-feature`)
3. Commit les changements (`git commit -m 'Add amazing feature'`)
4. Push vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrir une Pull Request

**Assurez-vous que tous les tests passent et que le code respecte les standards :**

```bash
composer check  # Pint + PHPStan
php artisan test
```

---

## 📝 Licence

Ce projet est sous licence MIT.

---

## 🎉 Credits

Développé avec ❤️ en utilisant Laravel et les meilleurs packages de l'écosystème.

**Packages principaux** :
- [Spatie Query Builder](https://github.com/spatie/laravel-query-builder)
- [Spatie MediaLibrary](https://github.com/spatie/laravel-medialibrary)
- [Spatie Translatable](https://github.com/spatie/laravel-translatable)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [L5 Swagger](https://github.com/DarkaOnLine/L5-Swagger)

---

## 📞 Support

- **Documentation** : Consultez `/docs` pour plus de détails
- **Swagger** : http://localhost/api/documentation
- **Issues** : Ouvrez une issue sur GitHub

---

**Happy Coding! 🚀✨**

