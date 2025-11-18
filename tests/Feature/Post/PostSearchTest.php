<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    // Créer des posts de test avec différents contenus
    Post::factory()->published()->create([
        'title' => [
            'en' => 'Introduction to Laravel',
            'fr' => 'Introduction à Laravel',
        ],
        'content' => [
            'en' => 'Laravel is a web application framework with expressive, elegant syntax.',
            'fr' => 'Laravel est un framework d\'application web avec une syntaxe expressive et élégante.',
        ],
        'views_count' => 150,
    ]);

    Post::factory()->published()->create([
        'title' => [
            'en' => 'Getting Started with PHP',
            'fr' => 'Débuter avec PHP',
        ],
        'content' => [
            'en' => 'PHP is a popular general-purpose scripting language.',
            'fr' => 'PHP est un langage de script polyvalent populaire.',
        ],
        'views_count' => 200,
    ]);

    Post::factory()->published()->create([
        'title' => [
            'en' => 'Advanced Laravel Techniques',
            'fr' => 'Techniques Laravel Avancées',
        ],
        'content' => [
            'en' => 'Learn advanced Laravel features and best practices.',
            'fr' => 'Apprenez les fonctionnalités avancées et les meilleures pratiques Laravel.',
        ],
        'views_count' => 75,
    ]);

    Post::factory()->published()->create([
        'title' => [
            'en' => 'Advanced Laravel Techniques',
            'fr' => 'Techniques Laravel Avancées',
        ],
        'content' => [
            'en' => 'Deep dive into Laravel.',
            'fr' => 'Plongée dans Laravel.',
        ],
    ]);
});

it('searches posts by title in english', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=Laravel', [
        'Accept-Language' => 'en',
    ]);

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);
    expect(collect($data)->pluck('title.en')->every(
        fn($title) => str_contains(strtolower($title), 'laravel')
    ))->toBeTrue();
});

it('searches posts by title in french', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=Laravel', [
        'Accept-Language' => 'fr',
    ]);

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);
    expect(collect($data)->pluck('title.fr')->every(
        fn($title) => str_contains(strtolower($title), 'laravel')
    ))->toBeTrue();
});

it('searches posts by content', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=framework');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

it('is case insensitive in search', function (): void {
    $response1 = getJson('/api/v1/posts?filter[search]=laravel');
    $response2 = getJson('/api/v1/posts?filter[search]=LARAVEL');
    $response3 = getJson('/api/v1/posts?filter[search]=LaRaVeL');

    $response1->assertSuccessful();
    $response2->assertSuccessful();
    $response3->assertSuccessful();

    expect($response1->json('meta.total'))->toBe($response2->json('meta.total'));
    expect($response1->json('meta.total'))->toBe($response3->json('meta.total'));
});

it('returns empty results for non-existent search', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=NonExistentKeyword123');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(0);
});

it('filters posts by user id', function (): void {
    $user = User::factory()->create();
    Post::factory()->published()->count(3)->create(['user_id' => $user->id]);

    $response = getJson("/api/v1/posts?filter[user_id]={$user->id}");

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(3);
});

it('sorts posts by published date descending', function (): void {
    $response = getJson('/api/v1/posts?sort=-published_at');

    $response->assertSuccessful();

    $dates = collect($response->json('data'))
        ->whereNotNull('publishedAt')
        ->pluck('publishedAt.datetime')
        ->toArray();

    // Vérifier que les dates sont en ordre décroissant
    for ($i = 0; $i < count($dates) - 1; $i++) {
        expect(strtotime($dates[$i]))->toBeGreaterThanOrEqual(strtotime($dates[$i + 1]));
    }
});

it('sorts posts by views count descending', function (): void {
    $response = getJson('/api/v1/posts?sort=-views_count');

    $response->assertSuccessful();

    $views = collect($response->json('data'))
        ->pluck('viewsCount')
        ->toArray();

    // Vérifier que les vues sont en ordre décroissant
    for ($i = 0; $i < count($views) - 1; $i++) {
        expect($views[$i])->toBeGreaterThanOrEqual($views[$i + 1]);
    }
});

it('sorts posts by creation date ascending', function (): void {
    $response = getJson('/api/v1/posts?sort=created_at');

    $response->assertSuccessful();

    $dates = collect($response->json('data'))
        ->pluck('createdAt.datetime')
        ->toArray();

    // Vérifier que les dates sont en ordre croissant
    for ($i = 0; $i < count($dates) - 1; $i++) {
        expect(strtotime($dates[$i]))->toBeLessThanOrEqual(strtotime($dates[$i + 1]));
    }
});

it('applies default sort when none specified', function (): void {
    $response = getJson('/api/v1/posts');

    $response->assertSuccessful();

    // Par défaut, trié par -published_at
    $dates = collect($response->json('data'))
        ->whereNotNull('publishedAt')
        ->pluck('publishedAt.datetime')
        ->toArray();

    if (count($dates) > 1) {
        expect(strtotime($dates[0]))->toBeGreaterThanOrEqual(strtotime($dates[1]));
    }
});

it('paginates search results', function (): void {
    Post::factory()->published()->count(20)->create([
        'title' => [
            'en' => 'Test Post',
            'fr' => 'Article Test',
        ],
        'content' => [
            'en' => 'Content',
            'fr' => 'Contenu',
        ],
    ]);

    $response = getJson('/api/v1/posts?filter[search]=Test&per_page=5');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('meta.per_page'))->toBe(5);
});

it('includes author in search results when requested', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=Laravel&include=user');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'author' => ['id', 'name', 'email'],
                ],
            ],
        ]);
});

it('combines search, filter, sort, and pagination', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=Laravel&sort=-views_count&per_page=2&page=1');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect(count($data))->toBeLessThanOrEqual(2);

    expect(collect($data)->every(fn($post) => $post['status']['value'] === 'published'))->toBeTrue();

    if (count($data) > 1) {
        expect($data[0]['viewsCount'])->toBeGreaterThanOrEqual($data[1]['viewsCount']);
    }
});

it('searches in both title and content', function (): void {
    // Créer un post avec le mot "unique" seulement dans le contenu
    Post::factory()->published()->create([
        'title' => [
            'en' => 'Test Post',
            'fr' => 'Article Test',
        ],
        'content' => [
            'en' => 'This contains the unique keyword.',
            'fr' => 'Ceci contient le mot-clé unique.',
        ],
    ]);

    $response = getJson('/api/v1/posts?filter[search]=unique');

    $response->assertSuccessful();
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

it('handles special characters in search', function (): void {
    Post::factory()->published()->create([
        'title' => [
            'en' => 'C++ Programming',
            'fr' => 'Programmation C++',
        ],
        'content' => [
            'en' => 'Content about C++',
            'fr' => 'Contenu sur C++',
        ],
    ]);

    $response = getJson('/api/v1/posts?filter[search]=C++');

    $response->assertSuccessful();
    // Devrait gérer les caractères spéciaux sans erreur
});

it('returns posts with multilingue content correctly formatted', function (): void {
    $response = getJson('/api/v1/posts?filter[search]=Laravel');

    $response->assertSuccessful();

    $firstPost = $response->json('data.0');
    expect($firstPost['title'])->toHaveKeys(['en', 'fr']);
    expect($firstPost['content'])->toHaveKeys(['en', 'fr']);
    expect($firstPost['status'])->toHaveKeys(['value', 'label', 'color']);
});
