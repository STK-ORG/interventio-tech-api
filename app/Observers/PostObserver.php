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
            $title = $post->getTranslation('title', app()->getLocale());
            $post->slug = $this->generateUniqueSlug($title);
        }
    }

    /**
     * Handle the Post "updating" event.
     */
    public function updating(Post $post): void
    {
        // Régénérer le slug si le titre a changé
        if ($post->isDirty('title') && empty($post->slug)) {
            $title = $post->getTranslation('title', app()->getLocale());
            $post->slug = $this->generateUniqueSlug($title, $post->id);
        }
    }

    /**
     * Generate a unique slug
     */
    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $query = Post::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'");

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $count = $query->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }
}
