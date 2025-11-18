<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

it('lists all posts', function (): void {
    Post::factory()->published()->count(5)->create();

    $response = getJson('/v1/posts');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'status',
                    'viewsCount',
                ],
            ],
            'links',
            'meta',
        ]);
});

it('shows a specific post by slug', function (): void {
    $post = Post::factory()->published()->create(['slug' => 'test-post-123']);

    $response = getJson('/v1/posts/test-post-123');

    $response->assertSuccessful()
        ->assertJson([
            'id'   => $post->id,
            'slug' => 'test-post-123',
        ]);
});

it('increments view count when viewing post', function (): void {
    $post = Post::factory()->published()->create(['views_count' => 10]);

    $response = getJson("/v1/posts/{$post->slug}");

    $response->assertSuccessful();

    $post->refresh();
    expect($post->views_count)->toBe(11);
});

it('supports including author in post details', function (): void {
    $post = Post::factory()->published()->create();

    $response = getJson("/v1/posts/{$post->slug}?include=user");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'author' => [
                'id',
                'name',
                'email',
            ],
        ]);
});

it('allows authenticated users to create a post', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'My First Post',
                'fr' => 'Mon Premier Article',
            ],
            'content' => [
                'en' => 'This is the content in English.',
                'fr' => 'Ceci est le contenu en français.',
            ],
            'status'       => 'published',
            'published_at' => now()->toISOString(),
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'slug',
                'status',
            ],
            'message',
        ]);

    assertDatabaseHas('posts', [
        'user_id' => $this->user->id,
        'status'  => PostStatus::PUBLISHED->value,
    ]);
});

it('requires authentication to create post', function (): void {
    $response = postJson('/v1/posts', [
        'title' => [
            'en' => 'Test',
            'fr' => 'Test',
        ],
        'content' => [
            'en' => 'Content',
            'fr' => 'Contenu',
        ],
        'status' => 'published',
    ]);

    $response->assertUnauthorized();
});

it('validates required fields when creating post', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'content', 'status']);
});

it('validates multilingual title fields', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'English Title',
                // Manque 'fr'
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'published',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title.fr']);
});

it('automatically generates unique slug from title', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'My Amazing Post',
                'fr' => 'Mon Article Incroyable',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'published',
        ]);

    $response->assertCreated();

    $post = Post::latest()->first();
    expect($post->slug)->toStartWith('my-amazing-post');
});

it('ensures slug uniqueness', function (): void {
    // Créer un post existant avec un created_at dans le passé
    Post::factory()->create([
        'slug'       => 'test-post',
        'created_at' => now()->subSecond(),
    ]);

    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Test Post',
                'fr' => 'Article Test',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'published',
        ]);

    $response->assertCreated();

    $post = Post::latest()->first();
    expect($post->slug)->not->toBe('test-post');
    expect($post->slug)->toStartWith('test-post-');
});

it('uploads featured image when creating post', function (): void {
    $image = UploadedFile::fake()->image('featured.jpg', 800, 600);

    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Post with Image',
                'fr' => 'Article avec Image',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status'         => 'published',
            'featured_image' => $image,
        ]);

    $response->assertCreated();

    $post = Post::latest()->first();
    expect($post->hasMedia('featured_image'))->toBeTrue();
});

it('uploads gallery images when creating post', function (): void {
    $image1 = UploadedFile::fake()->image('gallery1.jpg');
    $image2 = UploadedFile::fake()->image('gallery2.jpg');

    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Post with Gallery',
                'fr' => 'Article avec Galerie',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status'  => 'published',
            'gallery' => [$image1, $image2],
        ]);

    $response->assertCreated();

    $post = Post::latest()->first();
    expect($post->getMedia('gallery')->count())->toBe(2);
});

it('allows owner to update their post', function (): void {
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $response = actingAs($this->user)
        ->putJson("/v1/posts/{$post->slug}", [
            'title' => [
                'en' => 'Updated Title',
                'fr' => 'Titre Mis à Jour',
            ],
            'status' => 'archived',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'status' => [
                    'value' => 'archived',
                ],
            ],
        ]);

    $post->refresh();
    expect($post->getTranslation('title', 'en'))->toBe('Updated Title');
    expect($post->status)->toBe(PostStatus::ARCHIVED);
});

it('updates slug when title changes', function (): void {
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'title'   => [
            'en' => 'Original Title',
            'fr' => 'Titre Original',
        ],
        'slug' => 'original-title-123',
    ]);

    $response = actingAs($this->user)
        ->putJson("/v1/posts/{$post->slug}", [
            'title' => [
                'en' => 'New Amazing Title',
                'fr' => 'Nouveau Titre Incroyable',
            ],
        ]);

    $response->assertSuccessful();

    $post->refresh();
    expect($post->slug)->toStartWith('new-amazing-title');
});

it('prevents non-owner from updating post', function (): void {
    $post = Post::factory()->create();
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)
        ->putJson("/v1/posts/{$post->slug}", [
            'title' => [
                'en' => 'Hacked Title',
                'fr' => 'Titre Piraté',
            ],
        ]);

    $response->assertForbidden();
});

it('allows owner to delete their post', function (): void {
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $response = actingAs($this->user)
        ->deleteJson("/v1/posts/{$post->slug}");

    $response->assertSuccessful();

    // Vérifier que c'est un soft delete
    expect(Post::withTrashed()->find($post->id)->trashed())->toBeTrue();
});

it('prevents non-owner from deleting post', function (): void {
    $post = Post::factory()->create();
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)
        ->deleteJson("/v1/posts/{$post->slug}");

    $response->assertForbidden();
});

it('validates post status enum', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Test',
                'fr' => 'Test',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'invalid_status',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('supports draft status', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Draft Post',
                'fr' => 'Brouillon',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'draft',
        ]);

    $response->assertCreated();

    $post = Post::latest()->first();
    expect($post->status)->toBe(PostStatus::DRAFT);
    expect($post->isDraft())->toBeTrue();
});

it('returns 404 for non-existent post', function (): void {
    $response = getJson('/v1/posts/non-existent-slug');

    $response->assertNotFound();
});

it('supports french language in post responses', function (): void {
    $response = actingAs($this->user)
        ->postJson('/v1/posts', [
            'title' => [
                'en' => 'Test',
                'fr' => 'Test',
            ],
            'content' => [
                'en' => 'Content',
                'fr' => 'Contenu',
            ],
            'status' => 'published',
        ], ['Accept-Language' => 'fr']);

    $response->assertCreated();
    expect($response->json('message'))->toBeString();
});
