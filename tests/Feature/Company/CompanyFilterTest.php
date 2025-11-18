<?php

declare(strict_types=1);

use App\Models\Company;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    // Créer des entreprises de test
    Company::factory()->create([
        'company_name' => 'Tech Solutions',
        'is_verified'  => true,
        'created_at'   => now()->subDays(5),
    ]);

    Company::factory()->create([
        'company_name' => 'Digital Services',
        'is_verified'  => false,
        'created_at'   => now()->subDays(2),
    ]);

    Company::factory()->create([
        'company_name' => 'Innovation Hub Tech',
        'is_verified'  => true,
        'created_at'   => now()->subDay(),
    ]);
});

it('filters companies by name', function (): void {
    $response = getJson('/v1/companies?filter[company_name]=Tech');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect(collect($data)->pluck('companyName')->every(
        fn($name) => str_contains(strtolower($name), 'tech')
    ))->toBeTrue();
});

it('filters companies by verification status', function (): void {
    $response = getJson('/v1/companies?filter[is_verified]=1');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect(collect($data)->pluck('isVerified')->every(fn($verified) => $verified))->toBeTrue();
});

it('filters companies by non-verified status', function (): void {
    $response = getJson('/v1/companies?filter[is_verified]=0');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['isVerified'])->toBeFalse();
});

it('combines multiple filters', function (): void {
    $response = getJson('/v1/companies?filter[company_name]=Tech&filter[is_verified]=1');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect(collect($data)->every(
        fn($company)
        => str_contains(strtolower($company['companyName']), 'tech') && $company['isVerified']
    ))->toBeTrue();
});

it('sorts companies by name ascending', function (): void {
    $response = getJson('/v1/companies?sort=company_name');

    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('companyName')->toArray();
    $sorted = $names;
    sort($sorted);
    expect($names)->toBe($sorted);
});

it('sorts companies by creation date descending', function (): void {
    $response = getJson('/v1/companies?sort=-created_at');

    $response->assertSuccessful();

    $dates = collect($response->json('data'))
        ->pluck('createdAt.datetime')
        ->toArray();

    // Vérifier que les dates sont en ordre décroissant
    for ($i = 0; $i < count($dates) - 1; $i++) {
        expect(strtotime($dates[$i]))->toBeGreaterThanOrEqual(strtotime($dates[$i + 1]));
    }
});

it('sorts companies by verification date', function (): void {
    $response = getJson('/v1/companies?sort=-verified_at');

    $response->assertSuccessful();
    expect($response->json('data'))->toBeArray();
});

it('paginates company results', function (): void {
    Company::factory()->count(20)->create();

    $response = getJson('/v1/companies?per_page=5');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('meta.per_page'))->toBe(5);
});

it('navigates to next page', function (): void {
    Company::factory()->count(20)->create();

    $response = getJson('/v1/companies?page=2&per_page=5');

    $response->assertSuccessful();
    expect($response->json('meta.current_page'))->toBe(2);
});

it('includes user in company list when requested', function (): void {
    $response = getJson('/v1/companies?include=user');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'owner' => ['id', 'name', 'email'],
                ],
            ],
        ]);
});

it('combines filtering, sorting, and pagination', function (): void {
    Company::factory()->count(10)->verified()->create(['company_name' => 'Tech Company']);
    Company::factory()->count(10)->unverified()->create();

    $response = getJson('/v1/companies?filter[is_verified]=1&sort=-created_at&per_page=5&page=1');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(5);
    expect(collect($data)->every(fn($company) => $company['isVerified']))->toBeTrue();
});

it('returns empty array when no companies match filters', function (): void {
    $response = getJson('/v1/companies?filter[company_name]=NonExistentCompany');

    $response->assertSuccessful();
    expect($response->json('data'))->toBeArray()->toHaveCount(0);
});

it('handles invalid filter values gracefully', function (): void {
    $response = getJson('/v1/companies?filter[is_verified]=invalid');

    // Devrait soit ignorer le filtre invalide, soit retourner une erreur 400
    expect($response->status())->toBeIn([200, 400]);
});

it('applies default sort when none specified', function (): void {
    $response = getJson('/v1/companies');

    $response->assertSuccessful();

    // Par défaut, trié par -created_at (plus récent en premier)
    $dates = collect($response->json('data'))
        ->pluck('createdAt.datetime')
        ->toArray();

    if (count($dates) > 1) {
        expect(strtotime($dates[0]))->toBeGreaterThanOrEqual(strtotime($dates[1]));
    }
});
