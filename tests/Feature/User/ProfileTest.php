<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

it('returns authenticated user profile', function (): void {
    $response = actingAs($this->user)
        ->getJson('/v1/users/me');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'accountType',
            'address',
            'isProfessional',
            'isPrivate',
            'createdAt',
            'updatedAt',
        ])
        ->assertJson([
            'id'    => $this->user->id,
            'name'  => $this->user->name,
            'email' => $this->user->email,
        ]);
});

it('supports including professional profile', function (): void {
    $user = User::factory()->professional()->create();

    $response = actingAs($user)
        ->getJson('/v1/users/me?include=professionalProfile');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'professionalProfile' => [
                'id',
                'companyName',
                'cfeNumber',
            ],
        ]);
});

it('supports including companies', function (): void {
    Company::factory()->count(2)->create(['user_id' => $this->user->id]);

    $response = actingAs($this->user)
        ->getJson('/v1/users/me?include=companies');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'companies' => [
                '*' => ['id', 'companyName'],
            ],
        ])
        ->assertJsonCount(2, 'companies');
});

it('supports including posts', function (): void {
    Post::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = actingAs($this->user)
        ->getJson('/v1/users/me?include=posts');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'posts' => [
                '*' => ['id', 'title', 'slug'],
            ],
        ])
        ->assertJsonCount(3, 'posts');
});

it('supports including multiple relations', function (): void {
    $user = User::factory()->professional()->create();
    Company::factory()->count(2)->create(['user_id' => $user->id]);
    Post::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)
        ->getJson('/v1/users/me?include=professionalProfile,companies,posts');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'professionalProfile',
            'companies',
            'posts',
        ])
        ->assertJsonCount(2, 'companies')
        ->assertJsonCount(3, 'posts');
});

it('requires authentication', function (): void {
    $response = getJson('/v1/users/me');

    $response->assertUnauthorized();
});

it('updates user profile', function (): void {
    $response = actingAs($this->user)
        ->putJson('/v1/users/me', [
            'name'    => 'Updated Name',
            'email'   => 'updated@example.com',
            'address' => 'New Address',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'user' => [
                'name'    => 'Updated Name',
                'email'   => 'updated@example.com',
                'address' => 'New Address',
            ],
        ]);

    $this->user->refresh();
    expect($this->user->name)->toBe('Updated Name');
    expect($this->user->email)->toBe('updated@example.com');
});

it('updates user password', function (): void {
    $response = actingAs($this->user)
        ->putJson('/v1/users/me', [
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertSuccessful();

    // Vérifier que le nouveau mot de passe fonctionne
    $loginResponse = postJson('/v1/auth/login', [
        'email'    => $this->user->email,
        'password' => 'NewPassword123!',
    ]);

    $loginResponse->assertSuccessful();
});

it('validates email uniqueness on update', function (): void {
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    $response = actingAs($this->user)
        ->putJson('/v1/users/me', [
            'email' => 'other@example.com',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('uploads user avatar', function (): void {
    $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

    $response = actingAs($this->user)
        ->postJson('/v1/users/me/avatar', [
            'avatar' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'avatar' => ['url', 'thumb', 'medium'],
            ],
            'avatar_url',
            'message',
        ]);

    // Vérifier que l'avatar a été enregistré
    $this->user->refresh();
    expect($this->user->hasMedia('avatar'))->toBeTrue();
});

it('replaces existing avatar on upload', function (): void {
    // Upload premier avatar
    $file1 = UploadedFile::fake()->image('avatar1.jpg');
    actingAs($this->user)->postJson('/v1/users/me/avatar', ['avatar' => $file1]);

    // Upload deuxième avatar
    $file2 = UploadedFile::fake()->image('avatar2.jpg');
    $response = actingAs($this->user)->postJson('/v1/users/me/avatar', ['avatar' => $file2]);

    $response->assertSuccessful();

    // Vérifier qu'il n'y a qu'un seul avatar
    $this->user->refresh();
    expect($this->user->getMedia('avatar')->count())->toBe(1);
});

it('validates avatar file type', function (): void {
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    $response = actingAs($this->user)
        ->postJson('/v1/users/me/avatar', [
            'avatar' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['avatar']);
});

it('validates avatar file size', function (): void {
    $file = UploadedFile::fake()->image('huge-avatar.jpg')->size(5000); // 5MB

    $response = actingAs($this->user)
        ->postJson('/v1/users/me/avatar', [
            'avatar' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['avatar']);
});

it('deletes user avatar', function (): void {
    // D'abord uploader un avatar
    $file = UploadedFile::fake()->image('avatar.jpg');
    actingAs($this->user)->postJson('/v1/users/me/avatar', ['avatar' => $file]);

    // Ensuite le supprimer
    $response = actingAs($this->user)
        ->deleteJson('/v1/users/me/avatar');

    $response->assertSuccessful()
        ->assertJson([
            'user' => [
                'avatar' => null,
            ],
        ]);

    // Vérifier que l'avatar a été supprimé
    $this->user->refresh();
    expect($this->user->hasMedia('avatar'))->toBeFalse();
});

it('handles deleting non-existent avatar gracefully', function (): void {
    $response = actingAs($this->user)
        ->deleteJson('/v1/users/me/avatar');

    $response->assertSuccessful();
});

it('supports french language in profile responses', function (): void {
    $response = actingAs($this->user)
        ->putJson('/v1/users/me', [
            'name' => 'Updated Name',
        ], ['Accept-Language' => 'fr']);

    $response->assertSuccessful();
    expect($response->json('message'))->toBeString();
});

it('supports selective includes on profile update', function (): void {
    $user = User::factory()->professional()->create();

    $response = actingAs($user)
        ->putJson('/v1/users/me?include=professionalProfile', [
            'name' => 'Updated Name',
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'professionalProfile',
            ],
        ]);
});
