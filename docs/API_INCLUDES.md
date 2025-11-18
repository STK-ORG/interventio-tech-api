# 📚 API - Inclusion Flexible des Relations avec Spatie Query Builder

## 🎯 Concept

Avec **Spatie Query Builder**, votre API permet au client de décider quelles relations inclure dans la réponse via le paramètre `?include=`. C'est beaucoup plus flexible et performant que de toujours charger toutes les relations !

## 🚀 Utilisation

### Endpoints Supportés

Tous les endpoints utilisateur supportent l'inclusion flexible :

| Endpoint | Méthode | Relations Disponibles |
|----------|---------|----------------------|
| `/api/v1/users/me` | GET | `professionalProfile`, `companies`, `posts` |
| `/api/v1/users/me` | PUT | `professionalProfile`, `companies`, `posts` |
| `/api/v1/users/me/avatar` | POST | `professionalProfile`, `companies`, `posts` |
| `/api/v1/users/me/avatar` | DELETE | `professionalProfile`, `companies`, `posts` |
| `/api/v1/auth/register` | POST | `professionalProfile`, `companies`, `posts` |
| `/api/v1/auth/login` | POST | `professionalProfile`, `companies`, `posts` |

## 📖 Exemples d'Utilisation

### 1️⃣ Sans Inclusions (Réponse Minimale)

```bash
GET /api/v1/users/me
```

**Réponse** : Uniquement les données de l'utilisateur, sans relations.

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "accountType": {
    "value": "professional",
    "label": "Compte Professionnel"
  },
  "address": "123 Main Street, Lomé",
  "isProfessional": true,
  "isPrivate": false
}
```

**SQL Exécuté** : `1 requête`
```sql
SELECT * FROM users WHERE id = 1
```

---

### 2️⃣ Inclure Uniquement le Profil Professionnel

```bash
GET /api/v1/users/me?include=professionalProfile
```

**Réponse** : Utilisateur + Profil Professionnel

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "professionalProfile": {
    "id": 1,
    "companyName": "Tech Solutions SARL",
    "cfeNumber": "CFE-2024-001"
  }
}
```

**SQL Exécuté** : `2 requêtes` (optimisées avec eager loading)
```sql
SELECT * FROM users WHERE id = 1
SELECT * FROM professional_profiles WHERE user_id IN (1)
```

---

### 3️⃣ Inclure Plusieurs Relations

```bash
GET /api/v1/users/me?include=professionalProfile,companies
```

**Réponse** : Utilisateur + Profil + Entreprises

```json
{
  "id": 1,
  "name": "John Doe",
  "professionalProfile": {
    "id": 1,
    "companyName": "Tech Solutions SARL"
  },
  "companies": [
    {
      "id": 1,
      "companyName": "Digital Services",
      "isVerified": true
    },
    {
      "id": 2,
      "companyName": "Innovation Hub",
      "isVerified": false
    }
  ]
}
```

**SQL Exécuté** : `3 requêtes` (optimisées)
```sql
SELECT * FROM users WHERE id = 1
SELECT * FROM professional_profiles WHERE user_id IN (1)
SELECT * FROM companies WHERE user_id IN (1)
```

---

### 4️⃣ Inclure Toutes les Relations

```bash
GET /api/v1/users/me?include=professionalProfile,companies,posts
```

**Réponse** : Données complètes de l'utilisateur

```json
{
  "id": 1,
  "name": "John Doe",
  "professionalProfile": { ... },
  "companies": [ ... ],
  "posts": [
    {
      "id": 1,
      "title": {
        "en": "My First Post",
        "fr": "Mon Premier Article"
      },
      "status": {
        "value": "published",
        "label": "Publié"
      }
    }
  ]
}
```

**SQL Exécuté** : `4 requêtes` (optimisées)
```sql
SELECT * FROM users WHERE id = 1
SELECT * FROM professional_profiles WHERE user_id IN (1)
SELECT * FROM companies WHERE user_id IN (1)
SELECT * FROM posts WHERE user_id IN (1)
```

---

### 5️⃣ Avec Authentification (Register/Login)

```bash
POST /api/v1/auth/register?include=professionalProfile
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "account_type": "professional",
  "company_name": "My Company",
  "cfe_number": "CFE-2024-100"
}
```

**Réponse** : Utilisateur créé avec son profil professionnel directement inclus

```json
{
  "user": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "professionalProfile": {
      "id": 2,
      "companyName": "My Company",
      "cfeNumber": "CFE-2024-100"
    }
  },
  "access_token": "3|xyz123...",
  "refresh_token": "4|abc456...",
  "token_type": "Bearer",
  "expires_in": 1800,
  "message": "Registration successful"
}
```

---

### 6️⃣ Mise à Jour de Profil avec Inclusions Sélectives

```bash
PUT /api/v1/users/me?include=companies
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "name": "John Doe Updated",
  "email": "john.updated@example.com"
}
```

**Réponse** : Utilisateur mis à jour avec uniquement ses entreprises

```json
{
  "user": {
    "id": 1,
    "name": "John Doe Updated",
    "email": "john.updated@example.com",
    "companies": [...]
  },
  "message": "Profile updated successfully"
}
```

---

## 🎨 Avantages

### ✅ Performance Optimisée

| Scénario | Sans Query Builder | Avec Query Builder |
|----------|-------------------|--------------------|
| **Besoin minimal** | 4 requêtes (toutes) | 1 requête (user) |
| **Besoin partiel** | 4 requêtes (toutes) | 2-3 requêtes (selon besoin) |
| **Données transférées** | 100% | 20-50% selon besoin |

### ✅ Flexibilité Client

```javascript
// Frontend - Dashboard (besoin complet)
fetch('/api/v1/users/me?include=professionalProfile,companies,posts')

// Frontend - Header (besoin minimal)
fetch('/api/v1/users/me')

// Frontend - Profile Page (besoin partiel)
fetch('/api/v1/users/me?include=professionalProfile,companies')
```

### ✅ Protection N+1

Spatie Query Builder utilise automatiquement **eager loading** :

```php
// ❌ AVANT - Toujours 4 requêtes
$user->load(['professionalProfile', 'companies', 'posts']);

// ✅ APRÈS - 1 à 4 requêtes selon le besoin
QueryBuilder::for(User::where('id', $user->id))
    ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
    ->first();
```

### ✅ Standard API REST

Le paramètre `?include=` est un **standard largement adopté** :
- JSON:API
- GraphQL-like REST
- OpenAPI/Swagger

---

## 🔒 Sécurité

Les relations autorisées sont **explicitement définies** dans chaque controller :

```php
->allowedIncludes(['professionalProfile', 'companies', 'posts'])
```

❌ **Tentative d'inclusion non autorisée :**
```bash
GET /api/v1/users/me?include=passwords
```
**Réponse :** `400 Bad Request` - Relation non autorisée

---

## 📊 Comparaison Avant/Après

### ❌ Approche Rigide (Avant)

```php
// Controller
return new UserResource(
    $request->user()->load(['professionalProfile', 'companies', 'posts'])
);
```

**Problèmes :**
- ❌ Toujours 4 requêtes SQL
- ❌ Données inutiles transférées
- ❌ Performance dégradée
- ❌ Pas de contrôle client

### ✅ Approche Flexible (Maintenant)

```php
// Controller
$user = QueryBuilder::for(User::where('id', $request->user()->id))
    ->allowedIncludes(['professionalProfile', 'companies', 'posts'])
    ->first();

return new UserResource($user);
```

**Avantages :**
- ✅ 1 à 4 requêtes SQL (selon besoin)
- ✅ Données optimales
- ✅ Performance maximale
- ✅ Contrôle total par le client

---

## 🧪 Tests cURL

### Test Minimal
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost/api/v1/users/me
```

### Test avec Profil
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost/api/v1/users/me?include=professionalProfile"
```

### Test Complet
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost/api/v1/users/me?include=professionalProfile,companies,posts"
```

---

## 🌐 Support Multilingue

Le paramètre `include` fonctionne avec l'internationalisation :

```bash
GET /api/v1/users/me?include=posts
Accept-Language: fr
```

**Réponse :**
```json
{
  "posts": [
    {
      "title": {
        "en": "My First Post",
        "fr": "Mon Premier Article"
      },
      "status": {
        "label": "Publié"  // En français !
      }
    }
  ]
}
```

---

## 📚 Documentation Swagger

La documentation Swagger interactive est disponible à :
```
http://localhost/api/documentation
```

Tous les endpoints documentent le paramètre `?include=` avec des exemples !

---

## 💡 Best Practices

### ✅ À Faire

1. **Inclure uniquement ce dont vous avez besoin**
   ```bash
   # ✅ Bon
   GET /api/v1/users/me?include=professionalProfile
   ```

2. **Utiliser des inclusions spécifiques par page**
   ```javascript
   // Dashboard
   ?include=professionalProfile,companies,posts
   
   // Header
   (aucune inclusion)
   
   // Profile
   ?include=professionalProfile,companies
   ```

### ❌ À Éviter

1. **Toujours inclure toutes les relations**
   ```bash
   # ❌ Mauvais - Gaspillage de ressources
   GET /api/v1/users/me?include=professionalProfile,companies,posts
   ```

2. **Ne jamais utiliser d'inclusions**
   ```bash
   # ⚠️ Attention - Requêtes N+1 si vous accédez aux relations après
   GET /api/v1/users/me
   ```

---

## 🎯 Conclusion

L'utilisation de **Spatie Query Builder** avec le paramètre `?include=` offre :

- 🚀 **Meilleures performances** : Chargement optimisé
- 🎨 **Flexibilité totale** : Le client décide
- 🔒 **Sécurité** : Relations explicitement autorisées
- 📱 **Adapté mobile** : Réduction de la bande passante
- ✅ **Standard REST** : Conforme aux best practices

**C'est l'approche moderne pour les APIs RESTful professionnelles !** ✨

