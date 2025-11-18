# 🏢 API Entreprises & Publications - Guide Complet

## 📋 Table des Matières

1. [Gestion des Entreprises](#-gestion-des-entreprises)
2. [Gestion des Publications](#-gestion-des-publications)
3. [Recherche & Filtres](#-recherche--filtres)
4. [Exemples Pratiques](#-exemples-pratiques)

---

## 🏢 Gestion des Entreprises

### Vue d'Ensemble

Les utilisateurs avec un **compte professionnel** peuvent gérer plusieurs entreprises. Chaque entreprise peut avoir :
- Nom et numéro CFE
- Description multilingue (FR/EN)
- Adresse
- Logo
- Documents (PDF, DOC, DOCX)
- Statut de vérification

### Endpoints Disponibles

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/v1/companies` | Liste toutes les entreprises (paginé) | Non |
| `POST` | `/api/v1/companies` | Créer une nouvelle entreprise | Oui |
| `GET` | `/api/v1/companies/{id}` | Détails d'une entreprise | Non |
| `PUT` | `/api/v1/companies/{id}` | Modifier une entreprise | Oui |
| `DELETE` | `/api/v1/companies/{id}` | Supprimer une entreprise | Oui |

---

### 1️⃣ Lister les Entreprises

```bash
GET /api/v1/companies?include=user&filter[is_verified]=1&sort=-created_at
```

#### Paramètres de Requête

| Paramètre | Type | Description | Example |
|-----------|------|-------------|---------|
| `include` | string | Relations à inclure | `user` |
| `filter[company_name]` | string | Filtrer par nom (partiel) | `Tech` |
| `filter[is_verified]` | int | Filtrer par statut vérifié | `1` ou `0` |
| `sort` | string | Trier (préfixe `-` pour DESC) | `-created_at` |
| `page` | int | Numéro de page | `1` |
| `per_page` | int | Résultats par page | `15` |

#### Exemple de Réponse

```json
{
  "data": [
    {
      "id": 1,
      "companyName": "Tech Solutions SARL",
      "cfeNumber": "CFE-2024-001",
      "address": "456 Business Ave, Lomé",
      "description": {
        "en": "Digital services company",
        "fr": "Entreprise de services numériques"
      },
      "isVerified": true,
      "verifiedAt": {
        "datetime": "2025-01-15T10:00:00.000000Z",
        "humanDiff": "il y a 1 mois",
        "human": "Mer 15 Jan 2025 10:00:00"
      },
      "logo": "http://example.com/storage/logos/company-logo.png",
      "documents": [
        {
          "id": 1,
          "name": "registration.pdf",
          "url": "http://example.com/storage/documents/registration.pdf",
          "size": 204800
        }
      ],
      "owner": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "createdAt": {...},
      "updatedAt": {...}
    }
  ],
  "links": {
    "first": "http://example.com/api/v1/companies?page=1",
    "last": "http://example.com/api/v1/companies?page=5",
    "prev": null,
    "next": "http://example.com/api/v1/companies?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

---

### 2️⃣ Créer une Entreprise

```bash
POST /api/v1/companies
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data
```

#### Body (Form Data)

```json
{
  "company_name": "Innovation Hub SARL",
  "cfe_number": "CFE-2025-100",
  "address": "123 Tech Street, Lomé, Togo",
  "description": {
    "en": "Innovation and technology hub",
    "fr": "Centre d'innovation et de technologie"
  },
  "logo": <file> (optionnel, max 2MB, image),
  "documents": [<file1>, <file2>] (optionnel, max 5 fichiers de 10MB)
}
```

#### Réponse (201 Created)

```json
{
  "data": {
    "id": 5,
    "companyName": "Innovation Hub SARL",
    "cfeNumber": "CFE-2025-100",
    "isVerified": false,
    "verifiedAt": null,
    "logo": "http://example.com/storage/logos/innovation-hub-logo.png",
    "createdAt": {...}
  },
  "message": "Entreprise créée avec succès"
}
```

#### Erreurs Possibles

- **401 Unauthorized** : Token invalide
- **403 Forbidden** : Compte non professionnel
- **422 Validation Error** : Données invalides

---

### 3️⃣ Modifier une Entreprise

```bash
PUT /api/v1/companies/5
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

#### Body

```json
{
  "company_name": "Innovation Hub Updated",
  "address": "789 New Address, Lomé"
}
```

#### Réponse (200 OK)

```json
{
  "data": {
    "id": 5,
    "companyName": "Innovation Hub Updated",
    "address": "789 New Address, Lomé",
    "updatedAt": {...}
  },
  "message": "Entreprise mise à jour avec succès"
}
```

---

### 4️⃣ Supprimer une Entreprise

```bash
DELETE /api/v1/companies/5
Authorization: Bearer YOUR_TOKEN
```

#### Réponse (200 OK)

```json
{
  "message": "Entreprise supprimée avec succès"
}
```

**Note** : Suppression **douce** (soft delete) - Les données restent en base mais ne sont plus affichées.

---

## 📝 Gestion des Publications

### Vue d'Ensemble

**Tous les utilisateurs** (privés et professionnels) peuvent créer des publications. Chaque publication peut avoir :
- Titre et contenu multilingues (FR/EN)
- Statut (draft, published, archived)
- Image mise en avant
- Galerie d'images (max 10)
- Slug unique (généré automatiquement)
- Compteur de vues

### Endpoints Disponibles

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/v1/posts` | Liste toutes les publications (paginé) | Non |
| `POST` | `/api/v1/posts` | Créer une nouvelle publication | Oui |
| `GET` | `/api/v1/posts/{slug}` | Détails d'une publication | Non |
| `PUT` | `/api/v1/posts/{slug}` | Modifier une publication | Oui |
| `DELETE` | `/api/v1/posts/{slug}` | Supprimer une publication | Oui |

---

### 1️⃣ Lister les Publications

```bash
GET /api/v1/posts?include=user&filter[status]=published&filter[search]=technology&sort=-published_at
```

#### Paramètres de Requête

| Paramètre | Type | Description | Example |
|-----------|------|-------------|---------|
| `include` | string | Relations à inclure | `user` |
| `filter[search]` | string | Recherche dans titre/contenu | `technology` |
| `filter[status]` | string | Filtrer par statut | `published`, `draft`, `archived` |
| `filter[user_id]` | int | Filtrer par auteur | `1` |
| `sort` | string | Trier (préfixe `-` pour DESC) | `-published_at`, `-views_count` |
| `page` | int | Numéro de page | `1` |
| `per_page` | int | Résultats par page | `15` |

#### Exemple de Réponse

```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "en": "Introduction to Laravel",
        "fr": "Introduction à Laravel"
      },
      "slug": "introduction-to-laravel-123",
      "content": {
        "en": "Laravel is a web application framework...",
        "fr": "Laravel est un framework d'application web..."
      },
      "status": {
        "value": "published",
        "label": "Publié",
        "color": "green"
      },
      "viewsCount": 1523,
      "publishedAt": {
        "datetime": "2025-11-10T14:30:00.000000Z",
        "humanDiff": "il y a 1 semaine",
        "human": "Lun 10 Nov 2025 14:30:00"
      },
      "featuredImage": "http://example.com/images/post1.jpg",
      "gallery": [
        "http://example.com/images/gallery1.jpg",
        "http://example.com/images/gallery2.jpg"
      ],
      "author": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "isPublished": true,
      "isDraft": false,
      "isArchived": false,
      "createdAt": {...},
      "updatedAt": {...}
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### 2️⃣ Créer une Publication

```bash
POST /api/v1/posts
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data
```

#### Body (Form Data)

```json
{
  "title": {
    "en": "My First Post",
    "fr": "Mon Premier Article"
  },
  "content": {
    "en": "This is my first post content...",
    "fr": "Ceci est le contenu de mon premier article..."
  },
  "status": "published",
  "published_at": "2025-11-18T12:00:00Z" (optionnel),
  "featured_image": <file> (optionnel, max 5MB, image),
  "gallery": [<file1>, <file2>] (optionnel, max 10 images de 5MB)
}
```

#### Réponse (201 Created)

```json
{
  "data": {
    "id": 10,
    "title": {
      "en": "My First Post",
      "fr": "Mon Premier Article"
    },
    "slug": "my-first-post-456",
    "status": {
      "value": "published",
      "label": "Publié"
    },
    "viewsCount": 0,
    "createdAt": {...}
  },
  "message": "Publication créée avec succès"
}
```

---

### 3️⃣ Voir une Publication

```bash
GET /api/v1/posts/my-first-post-456?include=user
```

**Note** : Cette requête **incrémente automatiquement** le compteur de vues.

#### Réponse (200 OK)

```json
{
  "id": 10,
  "title": {...},
  "slug": "my-first-post-456",
  "content": {...},
  "viewsCount": 1,  // Incrémenté !
  "author": {...}
}
```

---

### 4️⃣ Modifier une Publication

```bash
PUT /api/v1/posts/my-first-post-456
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

#### Body

```json
{
  "title": {
    "en": "My Updated Post Title",
    "fr": "Mon Titre de Publication Mis à Jour"
  },
  "status": "published"
}
```

#### Réponse (200 OK)

```json
{
  "data": {
    "id": 10,
    "title": {
      "en": "My Updated Post Title",
      "fr": "Mon Titre de Publication Mis à Jour"
    },
    "slug": "my-updated-post-title-456",
    "updatedAt": {...}
  },
  "message": "Publication mise à jour avec succès"
}
```

**Note** : Le slug est **automatiquement mis à jour** si le titre change !

---

### 5️⃣ Supprimer une Publication

```bash
DELETE /api/v1/posts/my-first-post-456
Authorization: Bearer YOUR_TOKEN
```

#### Réponse (200 OK)

```json
{
  "message": "Publication supprimée avec succès"
}
```

**Note** : Suppression **douce** (soft delete).

---

## 🔍 Recherche & Filtres Avancés

### Recherche Intelligente dans les Publications

La recherche est **sensible à la langue** :

```bash
# Recherche en français
GET /api/v1/posts?filter[search]=laravel
Accept-Language: fr
# → Cherche dans title.fr et content.fr

# Recherche en anglais
GET /api/v1/posts?filter[search]=laravel
Accept-Language: en
# → Cherche dans title.en et content.en
```

### Combinaison de Filtres

```bash
# Publications publiées de l'utilisateur 5, triées par vues
GET /api/v1/posts?filter[user_id]=5&filter[status]=published&sort=-views_count

# Entreprises vérifiées dont le nom contient "Tech", triées par date
GET /api/v1/companies?filter[company_name]=Tech&filter[is_verified]=1&sort=-created_at

# Recherche "Laravel" dans les posts publiés, avec l'auteur inclus
GET /api/v1/posts?filter[search]=Laravel&filter[status]=published&include=user
```

---

## 💡 Exemples Pratiques

### Frontend React - Liste de Publications avec Recherche

```typescript
import { useState, useEffect } from 'react';

function PostsList() {
  const [posts, setPosts] = useState([]);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('published');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchPosts();
  }, [search, status]);

  const fetchPosts = async () => {
    setLoading(true);
    
    const params = new URLSearchParams({
      'filter[status]': status,
      'include': 'user',
      'sort': '-published_at',
    });

    if (search) {
      params.append('filter[search]', search);
    }

    const response = await fetch(
      `/api/v1/posts?${params.toString()}`,
      {
        headers: {
          'Accept-Language': 'fr',
        }
      }
    );

    const data = await response.json();
    setPosts(data.data);
    setLoading(false);
  };

  return (
    <div>
      {/* Barre de recherche */}
      <input
        type="text"
        placeholder="Rechercher..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />

      {/* Filtre par statut */}
      <select value={status} onChange={(e) => setStatus(e.target.value)}>
        <option value="published">Publié</option>
        <option value="draft">Brouillon</option>
        <option value="archived">Archivé</option>
      </select>

      {/* Liste des posts */}
      {loading ? (
        <p>Chargement...</p>
      ) : (
        <ul>
          {posts.map(post => (
            <li key={post.id}>
              <h2>{post.title.fr}</h2>
              <p>Par {post.author.name} - {post.viewsCount} vues</p>
              <span className={`status ${post.status.color}`}>
                {post.status.label}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
```

### Création d'une Entreprise avec Upload de Logo

```typescript
async function createCompany(formData: FormData) {
  const token = localStorage.getItem('token');

  const response = await fetch('/api/v1/companies', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept-Language': 'fr',
    },
    body: formData, // Contient company_name, cfe_number, logo, etc.
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  const data = await response.json();
  return data;
}

// Utilisation
const formData = new FormData();
formData.append('company_name', 'Tech Solutions');
formData.append('cfe_number', 'CFE-2025-001');
formData.append('address', 'Lomé, Togo');
formData.append('description[fr]', 'Description en français');
formData.append('description[en]', 'English description');
formData.append('logo', logoFile); // File from input

const company = await createCompany(formData);
console.log('Entreprise créée:', company);
```

---

## 🎯 Résumé des Fonctionnalités

### ✅ Entreprises

- ✅ Création/Modification/Suppression (soft delete)
- ✅ Support multilingue (FR/EN)
- ✅ Upload de logo et documents
- ✅ Filtrage par nom et statut de vérification
- ✅ Tri par date de création ou vérification
- ✅ Pagination
- ✅ Inclusion flexible des relations

### ✅ Publications

- ✅ Création/Modification/Suppression (soft delete)
- ✅ Support multilingue (FR/EN)
- ✅ **Recherche intelligente** sensible à la langue
- ✅ Filtrage par statut, auteur
- ✅ Tri par date de publication, nombre de vues
- ✅ Slug unique généré automatiquement
- ✅ Compteur de vues automatique
- ✅ Upload d'image mise en avant et galerie
- ✅ Pagination
- ✅ Inclusion flexible des relations

### ✅ Sécurité & Autorisation

- ✅ Seuls les utilisateurs authentifiés peuvent créer/modifier/supprimer
- ✅ Seuls les propriétaires peuvent modifier leurs propres ressources
- ✅ Les Policies Laravel gèrent les autorisations
- ✅ Comptes professionnels requis pour créer des entreprises

### ✅ Performance

- ✅ Spatie Query Builder pour filtres optimisés
- ✅ Eager loading automatique (évite N+1)
- ✅ Pagination pour grandes listes
- ✅ Inclusion flexible (ne charge que ce qui est nécessaire)

---

## 📚 Documentation Swagger

Toute l'API est documentée dans **Swagger** :

```
http://localhost/api/documentation
```

Vous pouvez **tester directement** tous les endpoints depuis cette interface interactive ! 🚀

