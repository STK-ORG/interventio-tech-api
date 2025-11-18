<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/register';
});

it('allows a user to register with private account', function (): void {
    $userData = [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'private',
        'address'               => '123 Main Street, Lomé',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'accountType',
                'isProfessional',
                'isPrivate',
            ],
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'message',
        ])
        ->assertJson([
            'user' => [
                'name'           => 'John Doe',
                'email'          => 'john@example.com',
                'isProfessional' => false,
                'isPrivate'      => true,
            ],
            'token_type' => 'Bearer',
        ]);

    assertDatabaseHas('users', [
        'email'        => 'john@example.com',
        'account_type' => AccountType::PRIVATE->value,
    ]);
});

it('allows a user to register with professional account', function (): void {
    $userData = [
        'name'                  => 'Jane Smith',
        'email'                 => 'jane@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'professional',
        'address'               => '456 Business Ave, Lomé',
        'company_name'          => 'Tech Solutions SARL',
        'cfe_number'            => 'CFE-2025-TEST-001',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertCreated()
        ->assertJson([
            'user' => [
                'isProfessional' => true,
                'isPrivate'      => false,
            ],
        ]);

    assertDatabaseHas('users', [
        'email'        => 'jane@example.com',
        'account_type' => AccountType::PROFESSIONAL->value,
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->professionalProfile)->not->toBeNull();
    expect($user->professionalProfile->company_name)->toBe('Tech Solutions SARL');
});

it('requires professional fields when registering with professional account', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'professional',
        // Manque company_name et cfe_number
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['company_name', 'cfe_number']);
});

it('validates email format', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'invalid-email',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'private',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates password confirmation', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'DifferentPassword',
        'account_type'          => 'private',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('prevents duplicate email registration', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'existing@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'private',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates account type', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'invalid_type',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['account_type']);
});

it('supports french language in responses', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'private',
    ];

    $response = postJson($this->endpoint, $userData, ['Accept-Language' => 'fr']);

    $response->assertCreated();
    expect($response->json('message'))->toBeString();
});

it('returns both access and refresh tokens', function (): void {
    $userData = [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'private',
    ];

    $response = postJson($this->endpoint, $userData);

    $response->assertCreated();

    $data = $response->json();
    expect($data['access_token'])->toBeString();
    expect($data['refresh_token'])->toBeString();
    expect($data['access_token'])->not->toBe($data['refresh_token']);
    expect($data['expires_in'])->toBe(1800); // 30 minutes en secondes
});

it('supports including professional profile in registration response', function (): void {
    $userData = [
        'name'                  => 'Jane Smith',
        'email'                 => 'jane@example.com',
        'password'              => 'Password123!',
        'password_confirmation' => 'Password123!',
        'account_type'          => 'professional',
        'company_name'          => 'Tech Solutions',
        'cfe_number'            => 'CFE-2025-001',
    ];

    $response = postJson($this->endpoint . '?include=professionalProfile', $userData);

    $response->assertCreated()
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
