# 📱 Système de Feed Public - Documentation

## 🎯 Vue d'Ensemble

Le système de feed public permet à **tous les utilisateurs** (authentifiés ou non) de consulter les posts publiés, créant ainsi une expérience de type réseau social.

---

## 🔓 Accès Public vs Authentifié

### Pour les Invités (Non authentifiés)
- ✅ Peuvent voir **uniquement les posts publiés**
- ✅ Peuvent rechercher dans les posts publiés  
- ✅ Peuvent filtrer par auteur (`user_id`)
- ✅ Peuvent trier par date, vues, etc.
- ❌ Ne voient **jamais** les brouillons

### Pour les Utilisateurs Authentifiés
- ✅ Voient **tous les posts publiés**
- ✅ Voient **leurs propres brouillons**
- ❌ Ne voient **pas** les brouillons des autres utilisateurs

---

## 📡 Endpoints Publics

### 1. **GET /api/v1/posts** - Feed Public
```bash
# Exemple : Invité
curl https://api.example.com/api/v1/posts

# Exemple : Avec recherche
curl https://api.example.com/api/v1/posts?filter[search]=Laravel

# Exemple : Filtrer par auteur
curl https://api.example.com/api/v1/posts?filter[user_id]=123

# Exemple : Trier par vues
curl https://api.example.com/api/v1/posts?sort=-views_count

# Exemple : Pagination
curl https://api.example.com/api/v1/posts?page=2&per_page=20
```

**Réponse** :
```json
{
  "data": [
    {
      "id": 1,
      "title": {"en": "My Post", "fr": "Mon Article"},
      "slug": "my-post",
      "content": {"en": "...", "fr": "..."},
      "status": {
        "value": "published",
        "label": "Published",
        "color": "green"
      },
      "viewsCount": 150,
      "publishedAt": {...},
      "author": {...},
      "createdAt": {...},
      "updatedAt": {...}
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. **GET /api/v1/posts/{slug}** - Détails d'un Post
```bash
# Exemple : Invité
curl https://api.example.com/api/v1/posts/my-first-post

# Exemple : Avec relation author
curl https://api.example.com/api/v1/posts/my-first-post?include=user
```

**Comportement** :
- ✅ Invités : Accès uniquement aux posts publiés
- ✅ Authentifiés : Accès aux posts publiés + leurs propres brouillons
- ❌ Erreur 404 : Si le post est un brouillon d'un autre utilisateur

---

## 🔑 Filtres Disponibles

| Filtre | Description | Exemple |
|--------|-------------|---------|
| `filter[search]` | Recherche dans titre et contenu (multilingue) | `?filter[search]=Laravel` |
| `filter[user_id]` | Posts d'un auteur spécifique | `?filter[user_id]=123` |
| `sort` | Tri (publiés, créés, vues) | `?sort=-views_count` |
| `include` | Inclure relations (user) | `?include=user` |
| `per_page` | Nombre de résultats par page | `?per_page=20` |
| `page` | Numéro de page | `?page=2` |

---

## 🛡️ Sécurité & Permissions

### Ce qui est PUBLIC (Pas d'authentification requise)
```
GET /api/v1/posts          → Liste des posts
GET /api/v1/posts/{slug}   → Détails d'un post
```

### Ce qui requiert AUTHENTIFICATION
```
POST   /api/v1/posts        → Créer un post
PUT    /api/v1/posts/{slug} → Modifier un post
DELETE /api/v1/posts/{slug} → Supprimer un post
```

### Logique de Sécurité

**Dans le Controller** :
```php
// INDEX - Liste
if (! $request->user()) {
    $query->where('status', PostStatus::PUBLISHED);
} else {
    $query->where(function ($q) use ($request): void {
        $q->where('status', PostStatus::PUBLISHED)
            ->orWhere('user_id', $request->user()->id);
    });
}

// SHOW - Détails
if (! $request->user() || $post->user_id !== $request->user()?->id) {
    if ($post->status !== PostStatus::PUBLISHED) {
        abort(404);
    }
}
```

---

## 📊 Cas d'Usage

### 1. Feed d'Accueil (Invités)
```javascript
// React/Vue Example
fetch('https://api.example.com/api/v1/posts?sort=-published_at&per_page=10')
  .then(res => res.json())
  .then(data => {
    // Afficher les 10 derniers posts publiés
    console.log(data.data);
  });
```

### 2. Feed Personnalisé (Authentifiés)
```javascript
// Avec token d'authentification
fetch('https://api.example.com/api/v1/posts?include=user', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
  .then(res => res.json())
  .then(data => {
    // Afficher posts publiés + brouillons de l'utilisateur
    console.log(data.data);
  });
```

### 3. Profil Public d'un Auteur
```javascript
// Posts publics d'un auteur spécifique
fetch('https://api.example.com/api/v1/posts?filter[user_id]=123&sort=-published_at')
  .then(res => res.json())
  .then(data => {
    // Afficher tous les posts publiés de l'auteur 123
    console.log(data.data);
  });
```

### 4. Recherche Publique
```javascript
// Recherche multilingue
fetch('https://api.example.com/api/v1/posts?filter[search]=Laravel', {
  headers: {
    'Accept-Language': 'fr'
  }
})
  .then(res => res.json())
  .then(data => {
    // Résultats en français
    console.log(data.data);
  });
```

### 5. Top Posts (Plus vus)
```javascript
// Posts les plus populaires
fetch('https://api.example.com/api/v1/posts?sort=-views_count&per_page=10')
  .then(res => res.json())
  .then(data => {
    // Top 10 posts par vues
    console.log(data.data);
  });
```

---

## 🧪 Tests Automatisés

### Tests du Feed Public (13 tests)
- ✅ `it allows guests to view published posts`
- ✅ `it allows guests to search published posts`
- ✅ `it allows guests to view a specific published post`
- ✅ `it prevents guests from viewing draft posts`
- ✅ `it allows authenticated users to view all published posts plus their own drafts`
- ✅ `it allows users to view their own draft posts`
- ✅ `it prevents users from viewing other users draft posts`
- ✅ `it increments view count for public posts`
- ✅ `it filters by user_id in public feed`
- ✅ `it sorts posts by published date in public feed`
- ✅ `it sorts posts by views count in public feed`
- ✅ `it includes author relationship in public feed`
- ✅ `it paginates public feed`

**Lancer les tests** :
```bash
php artisan test --filter=PublicFeedTest
```

---

## 🎨 Exemples d'Intégration Frontend

### React Component - Feed Public
```jsx
import { useState, useEffect } from 'react';

function PublicFeed() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('https://api.example.com/api/v1/posts?include=user')
      .then(res => res.json())
      .then(data => {
        setPosts(data.data);
        setLoading(false);
      });
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div className="feed">
      {posts.map(post => (
        <article key={post.id}>
          <h2>{post.title.en}</h2>
          <p>By {post.author?.name}</p>
          <p>{post.content.en.substring(0, 150)}...</p>
          <span>{post.viewsCount} views</span>
        </article>
      ))}
    </div>
  );
}
```

### Vue Component - Feed avec Recherche
```vue
<template>
  <div>
    <input v-model="searchQuery" @input="searchPosts" placeholder="Search posts...">
    <div v-for="post in posts" :key="post.id" class="post-card">
      <h3>{{ post.title.en }}</h3>
      <p>{{ post.content.en }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      posts: [],
      searchQuery: ''
    }
  },
  methods: {
    async searchPosts() {
      const res = await fetch(
        `https://api.example.com/api/v1/posts?filter[search]=${this.searchQuery}`
      );
      const data = await res.json();
      this.posts = data.data;
    }
  },
  mounted() {
    this.searchPosts();
  }
}
</script>
```

---

## 📈 Performance & Cache

Le feed public utilise le **système de cache** pour optimiser les performances :

- **TTL** : 5 minutes (posts changent fréquemment)
- **Clé de cache** : Basée sur les paramètres de requête, locale, et statut d'authentification
- **Invalidation** : Automatique lors de la création/modification/suppression de posts

**Exemple de clé** :
```
post.list.{hash_params}.en.guest
post.list.{hash_params}.fr.authenticated
```

---

## 🔄 Workflow Complet

```mermaid
graph TD
    A[Utilisateur visite le site] --> B{Authentifié?}
    B -->|Non| C[GET /api/v1/posts]
    B -->|Oui| D[GET /api/v1/posts avec token]
    C --> E[Voir posts publiés uniquement]
    D --> F[Voir posts publiés + ses brouillons]
    E --> G[Cliquer sur un post]
    F --> G
    G --> H[GET /api/v1/posts/{slug}]
    H --> I{Post publié?}
    I -->|Oui| J[Afficher le post]
    I -->|Non| K{Propriétaire?}
    K -->|Oui| J
    K -->|Non| L[404 Not Found]
```

---

## ✅ Résumé

| Fonctionnalité | Status |
|----------------|--------|
| Feed public pour invités | ✅ |
| Feed personnalisé pour authentifiés | ✅ |
| Recherche multilingue | ✅ |
| Filtres par auteur | ✅ |
| Tri personnalisé | ✅ |
| Pagination | ✅ |
| Compteur de vues | ✅ |
| Cache optimisé | ✅ |
| Tests automatisés (130 tests) | ✅ |
| Documentation Swagger | ✅ |

**Le système de feed public est 100% fonctionnel et prêt pour la production** ! 🚀

