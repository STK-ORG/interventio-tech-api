<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Str;

final class PostObserver
{
    /**
     * Handle the Post "creating" event.
     */
    public function creating(Post $post): void
    {
        // Générer automatiquement le slug si non fourni
        if (empty($post->slug)) {
            $locale = app()->getLocale();
            $title = '';

            // Le titre peut être un array si pas encore transformé par Spatie
            $titleAttribute = $post->getAttributes()['title'] ?? null;

            if (is_array($titleAttribute)) {
                // Si c'est déjà un array, prendre la locale courante ou 'en'
                $title = $titleAttribute[$locale] ?? $titleAttribute['en'] ?? reset($titleAttribute);
            } else {
                // Sinon, essayer de récupérer via getTranslation
                $title = $post->getTranslation('title', $locale, false);

                if (empty($title)) {
                    $title = $post->getTranslation('title', 'en', false);
                }

                if (empty($title)) {
                    $translations = $post->getTranslations('title');
                    $title = reset($translations) ?: 'post';
                }
            }

            $post->slug = $this->generateUniqueSlug($title ?: 'post');
        }
    }

    /**
     * Handle the Post "updating" event.
     */
    public function updating(Post $post): void
    {
        // Régénérer le slug si le titre a changé
        if ($post->isDirty('title')) {
            $title = $post->getTranslation('title', app()->getLocale());
            $post->slug = $this->generateUniqueSlug($title, $post->id);
        }
    }

    /**
     * Generate a unique slug (compatible avec MySQL et SQLite)
     */
    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        // Chercher si le slug existe déjà
        while (true) {
            $query = Post::withTrashed()->where('slug', $slug);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->doesntExist()) {
                break;
            }

            $counter++;
            $slug = "{$originalSlug}-{$counter}";

            // Limite de sécurité pour éviter une boucle infinie
            if ($counter > 1000) {
                $slug = "{$originalSlug}-" . uniqid();
                break;
            }
        }

        return $slug;
    }
}
