<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->endpoint = '/v1/auth/login';
    $this->user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('Password123!'),
    ]);
});

it('allows a user to login with valid credentials', function (): void {
    $response = postJson($this->endpoint, [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
            ],
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'message',
        ])
        ->assertJson([
            'user' => [
                'email' => 'test@example.com',
            ],
            'token_type' => 'Bearer',
            'expires_in' => 1800,
        ]);
});

it('rejects login with invalid email', function (): void {
    $response = postJson($this->endpoint, [
        'email'    => 'wrong@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects login with invalid password', function (): void {
    $response = postJson($this->endpoint, [
        'email'    => 'test@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires email field', function (): void {
    $response = postJson($this->endpoint, [
        'password' => 'Password123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires password field', function (): void {
    $response = postJson($this->endpoint, [
        'email' => 'test@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('returns both access and refresh tokens', function (): void {
    $response = postJson($this->endpoint, [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertSuccessful();

    $data = $response->json();
    expect($data['access_token'])->toBeString();
    expect($data['refresh_token'])->toBeString();
    expect($data['access_token'])->not->toBe($data['refresh_token']);
});

it('supports device name in login', function (): void {
    $response = postJson($this->endpoint, [
        'email'       => 'test@example.com',
        'password'    => 'Password123!',
        'device_name' => 'iPhone 14 Pro',
    ]);

    $response->assertSuccessful();

    // Vérifier que le token a été créé avec le bon nom
    expect($this->user->fresh()->tokens()->count())->toBe(2); // access + refresh
});

it('supports including professional profile in login response', function (): void {
    $professionalUser = User::factory()->professional()->create([
        'email'    => 'pro@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $response = postJson($this->endpoint . '?include=professionalProfile', [
        'email'    => 'pro@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'professionalProfile' => [
                    'id',
                    'companyName',
                    'cfeNumber',
                ],
            ],
        ]);
});

it('supports including companies in login response', function (): void {
    $response = postJson($this->endpoint . '?include=companies', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'companies',
            ],
        ]);
});

it('supports french language in responses', function (): void {
    $response = postJson($this->endpoint, [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ], ['Accept-Language' => 'fr']);

    $response->assertSuccessful();
    expect($response->json('message'))->toBeString();
});

it('logs out user from current device', function (): void {
    // D'abord se connecter
    $loginResponse = postJson($this->endpoint, [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $token = $loginResponse->json('access_token');

    // Ensuite se déconnecter
    $response = postJson('/v1/auth/logout', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertSuccessful();

    // Vérifier que le token a été révoqué
    $oldTokenCount = $this->user->fresh()->tokens()->count();
    $newTokenCount = $this->user->fresh()->tokens()->count();
    expect($newTokenCount)->toBeLessThan($oldTokenCount + 2);
});

it('logs out user from all devices', function (): void {
    // Se connecter depuis deux appareils
    $login1 = postJson($this->endpoint, [
        'email'       => 'test@example.com',
        'password'    => 'Password123!',
        'device_name' => 'Device 1',
    ]);

    $login2 = postJson($this->endpoint, [
        'email'       => 'test@example.com',
        'password'    => 'Password123!',
        'device_name' => 'Device 2',
    ]);

    $token = $login1->json('access_token');

    // Se déconnecter de tous les appareils
    $response = postJson('/v1/auth/logout-all', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertSuccessful();

    // Vérifier que tous les tokens ont été révoqués
    expect($this->user->fresh()->tokens()->count())->toBe(0);
});
