<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('Password123!'),
    ]);
});

it('refreshes an access token with valid refresh token', function (): void {
    // D'abord se connecter pour obtenir un refresh token
    $loginResponse = postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $refreshToken = $loginResponse->json('refresh_token');

    // Rafraîchir le token
    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'message',
        ])
        ->assertJson([
            'token_type' => 'Bearer',
            'expires_in' => 1800,
        ]);

    // Vérifier que c'est un nouveau token
    expect($response->json('access_token'))->not->toBe($loginResponse->json('access_token'));
    expect($response->json('refresh_token'))->not->toBe($refreshToken);
});

it('rejects invalid refresh token', function (): void {
    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'invalid-token-here',
    ]);

    $response->assertUnauthorized();
});

it('requires refresh token field', function (): void {
    $response = postJson('/api/v1/auth/refresh', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['refresh_token']);
});

it('rejects expired refresh token', function (): void {
    // Créer un refresh token expiré
    $token = $this->user->createToken(
        'refresh-test',
        ['refresh'],
        now()->subDay() // Expiré hier
    )->plainTextToken;

    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $token,
    ]);

    $response->assertUnauthorized();
});

it('revokes old refresh token after refresh', function (): void {
    // Se connecter
    $loginResponse = postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $oldRefreshToken = $loginResponse->json('refresh_token');
    $tokensBeforeRefresh = $this->user->fresh()->tokens()->count();

    // Rafraîchir
    $refreshResponse = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $oldRefreshToken,
    ]);

    $refreshResponse->assertSuccessful();

    // Tenter de réutiliser l'ancien refresh token
    $retryResponse = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $oldRefreshToken,
    ]);

    $retryResponse->assertUnauthorized();
});

it('supports device name in refresh', function (): void {
    $loginResponse = postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $refreshToken = $loginResponse->json('refresh_token');

    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
        'device_name'   => 'New Device',
    ]);

    $response->assertSuccessful();
});

it('rejects access token used as refresh token', function (): void {
    $loginResponse = postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $accessToken = $loginResponse->json('access_token');

    // Tenter d'utiliser l'access token comme refresh token
    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $accessToken,
    ]);

    $response->assertUnauthorized();
});

it('supports french language in responses', function (): void {
    $loginResponse = postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $refreshToken = $loginResponse->json('refresh_token');

    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ], ['Accept-Language' => 'fr']);

    $response->assertSuccessful();
    expect($response->json('message'))->toBeString();
});
