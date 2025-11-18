<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $titleEn = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'title'   => [
                'en' => $titleEn,
                'fr' => fake()->sentence(),
            ],
            'slug'    => Str::slug($titleEn) . '-' . fake()->unique()->numberBetween(1, 10000),
            'content' => [
                'en' => fake()->paragraphs(3, true),
                'fr' => fake()->paragraphs(3, true),
            ],
            'status'       => fake()->randomElement(PostStatus::cases()),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'views_count'  => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => PostStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => PostStatus::DRAFT,
            'published_at' => null,
        ]);
    }
}
