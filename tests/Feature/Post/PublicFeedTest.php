<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('allows guests to view published posts', function (): void {
    Post::factory()->count(3)->published()->create();
    Post::factory()->count(2)->draft()->create();

    $response = getJson('/api/v1/posts');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('allows guests to search published posts', function (): void {
    Post::factory()->published()->create([
        'title' => [
            'en' => 'Laravel Tutorial',
            'fr' => 'Tutoriel Laravel',
        ],
    ]);

    Post::factory()->draft()->create([
        'title' => [
            'en' => 'Laravel Advanced',
            'fr' => 'Laravel Avancé',
        ],
    ]);

    $response = getJson('/api/v1/posts?filter[search]=Laravel');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('allows guests to view a specific published post', function (): void {
    $post = Post::factory()->published()->create();

    $response = getJson("/api/v1/posts/{$post->slug}");

    $response->assertSuccessful();
    expect($response->json('id'))->toBe($post->id);
});

it('prevents guests from viewing draft posts', function (): void {
    $post = Post::factory()->draft()->create();

    $response = getJson("/api/v1/posts/{$post->slug}");

    $response->assertNotFound();
});

it('allows authenticated users to view all published posts plus their own drafts', function (): void {
    $otherUser = User::factory()->create();

    Post::factory()->published()->count(3)->create();
    Post::factory()->draft()->create(['user_id' => $this->user->id]);
    Post::factory()->draft()->create(['user_id' => $this->user->id]);
    Post::factory()->draft()->create(['user_id' => $otherUser->id]);

    $response = actingAs($this->user)->getJson('/api/v1/posts');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(5);
});

it('allows users to view their own draft posts', function (): void {
    $post = Post::factory()->draft()->create(['user_id' => $this->user->id]);

    $response = actingAs($this->user)->getJson("/api/v1/posts/{$post->slug}");

    $response->assertSuccessful();
    expect($response->json('id'))->toBe($post->id);
});

it('prevents users from viewing other users draft posts', function (): void {
    $otherUser = User::factory()->create();
    $post = Post::factory()->draft()->create(['user_id' => $otherUser->id]);

    $response = actingAs($this->user)->getJson("/api/v1/posts/{$post->slug}");

    $response->assertNotFound();
});

it('increments view count for public posts', function (): void {
    $post = Post::factory()->published()->create([
        'views_count' => 0,
        'created_at'  => now()->subSecond(),
    ]);

    getJson("/api/v1/posts/{$post->slug}");

    expect($post->fresh()->views_count)->toBe(1);
});

it('filters by user_id in public feed', function (): void {
    $author = User::factory()->create();

    Post::factory()->published()->count(2)->create(['user_id' => $author->id]);
    Post::factory()->published()->count(3)->create();

    $response = getJson("/api/v1/posts?filter[user_id]={$author->id}");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('sorts posts by published date in public feed', function (): void {
    $oldest = Post::factory()->published()->create([
        'published_at' => now()->subDays(3),
    ]);

    $newest = Post::factory()->published()->create([
        'published_at' => now()->subDays(1),
    ]);

    $middle = Post::factory()->published()->create([
        'published_at' => now()->subDays(2),
    ]);

    $response = getJson('/api/v1/posts?sort=-published_at');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($newest->id);
    expect($data[1]['id'])->toBe($middle->id);
    expect($data[2]['id'])->toBe($oldest->id);
});

it('sorts posts by views count in public feed', function (): void {
    $mostViewed = Post::factory()->published()->create(['views_count' => 100]);
    $leastViewed = Post::factory()->published()->create(['views_count' => 10]);
    $mediumViewed = Post::factory()->published()->create(['views_count' => 50]);

    $response = getJson('/api/v1/posts?sort=-views_count');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($mostViewed->id);
    expect($data[1]['id'])->toBe($mediumViewed->id);
    expect($data[2]['id'])->toBe($leastViewed->id);
});

it('includes author relationship in public feed', function (): void {
    $author = User::factory()->create(['name' => 'John Doe']);
    Post::factory()->published()->create(['user_id' => $author->id]);

    $response = getJson('/api/v1/posts?include=user');

    $response->assertSuccessful();
    $data = $response->json('data.0');

    expect($data)->toHaveKey('author');
    expect($data['author']['name'])->toBe('John Doe');
});

it('paginates public feed', function (): void {
    Post::factory()->published()->count(25)->create();

    $response = getJson('/api/v1/posts?per_page=10');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(10);
    expect($response->json('meta.total'))->toBe(25);
    expect($response->json('meta.last_page'))->toBe(3);
});
