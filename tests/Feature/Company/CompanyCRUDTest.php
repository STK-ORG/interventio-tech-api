<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->professionalUser = User::factory()->professional()->create();
    $this->privateUser = User::factory()->private()->create();
    Storage::fake('public');
});

it('lists all companies', function (): void {
    Company::factory()->count(5)->create();

    $response = getJson('/api/v1/companies');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'companyName',
                    'cfeNumber',
                    'isVerified',
                ],
            ],
            'links',
            'meta',
        ]);
});

it('shows a specific company', function (): void {
    $company = Company::factory()->create();

    $response = getJson("/api/v1/companies/{$company->id}");

    $response->assertSuccessful()
        ->assertJson([
            'id'          => $company->id,
            'companyName' => $company->company_name,
            'cfeNumber'   => $company->cfe_number,
        ]);
});

it('supports including user in company details', function (): void {
    $company = Company::factory()->create();

    $response = getJson("/api/v1/companies/{$company->id}?include=user");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'owner' => [
                'id',
                'name',
                'email',
            ],
        ]);
});

it('allows professional users to create a company', function (): void {
    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'New Tech Company',
            'cfe_number'   => 'CFE-2025-NEW-001',
            'address'      => 'Lomé, Togo',
            'description'  => [
                'en' => 'English description',
                'fr' => 'Description en français',
            ],
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'companyName',
                'cfeNumber',
            ],
            'message',
        ]);

    assertDatabaseHas('companies', [
        'company_name' => 'New Tech Company',
        'cfe_number'   => 'CFE-2025-NEW-001',
        'user_id'      => $this->professionalUser->id,
    ]);
});

it('prevents private users from creating companies', function (): void {
    $response = actingAs($this->privateUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'New Company',
            'cfe_number'   => 'CFE-2025-001',
        ]);

    $response->assertForbidden();
});

it('requires authentication to create company', function (): void {
    $response = postJson('/api/v1/companies', [
        'company_name' => 'New Company',
        'cfe_number'   => 'CFE-2025-001',
    ]);

    $response->assertUnauthorized();
});

it('validates required fields when creating company', function (): void {
    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['company_name', 'cfe_number']);
});

it('validates unique cfe_number', function (): void {
    Company::factory()->create(['cfe_number' => 'CFE-EXISTING']);

    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'New Company',
            'cfe_number'   => 'CFE-EXISTING',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['cfe_number']);
});

it('uploads logo when creating company', function (): void {
    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'New Company',
            'cfe_number'   => 'CFE-2025-001',
            'logo'         => $logo,
        ]);

    $response->assertCreated();

    $company = Company::latest()->first();
    expect($company->hasMedia('logo'))->toBeTrue();
});

it('uploads multiple documents when creating company', function (): void {
    $doc1 = UploadedFile::fake()->create('doc1.pdf', 100);
    $doc2 = UploadedFile::fake()->create('doc2.pdf', 150);

    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'New Company',
            'cfe_number'   => 'CFE-2025-001',
            'documents'    => [$doc1, $doc2],
        ]);

    $response->assertCreated();

    $company = Company::latest()->first();
    expect($company->getMedia('documents')->count())->toBe(2);
});

it('allows owner to update their company', function (): void {
    $company = Company::factory()->create(['user_id' => $this->professionalUser->id]);

    $response = actingAs($this->professionalUser)
        ->putJson("/api/v1/companies/{$company->id}", [
            'company_name' => 'Updated Company Name',
            'address'      => 'New Address',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'companyName' => 'Updated Company Name',
                'address'     => 'New Address',
            ],
        ]);

    $company->refresh();
    expect($company->company_name)->toBe('Updated Company Name');
});

it('prevents non-owner from updating company', function (): void {
    $company = Company::factory()->create();
    $otherUser = User::factory()->professional()->create();

    $response = actingAs($otherUser)
        ->putJson("/api/v1/companies/{$company->id}", [
            'company_name' => 'Hacked Name',
        ]);

    $response->assertForbidden();
});

it('validates cfe_number uniqueness on update', function (): void {
    $company1 = Company::factory()->create([
        'user_id'    => $this->professionalUser->id,
        'cfe_number' => 'CFE-001',
    ]);

    $company2 = Company::factory()->create([
        'user_id'    => $this->professionalUser->id,
        'cfe_number' => 'CFE-002',
    ]);

    $response = actingAs($this->professionalUser)
        ->putJson("/api/v1/companies/{$company1->id}", [
            'cfe_number' => 'CFE-002', // Essayer de dupliquer
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['cfe_number']);
});

it('allows owner to delete their company', function (): void {
    $company = Company::factory()->create(['user_id' => $this->professionalUser->id]);

    $response = actingAs($this->professionalUser)
        ->deleteJson("/api/v1/companies/{$company->id}");

    $response->assertSuccessful();

    // Vérifier que c'est un soft delete
    expect(Company::withTrashed()->find($company->id)->trashed())->toBeTrue();
});

it('prevents non-owner from deleting company', function (): void {
    $company = Company::factory()->create();
    $otherUser = User::factory()->professional()->create();

    $response = actingAs($otherUser)
        ->deleteJson("/api/v1/companies/{$company->id}");

    $response->assertForbidden();
});

it('returns 404 for non-existent company', function (): void {
    $response = getJson('/api/v1/companies/99999');

    $response->assertNotFound();
});

it('supports french language in company responses', function (): void {
    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'Test Company',
            'cfe_number'   => 'CFE-2025-001',
        ], ['Accept-Language' => 'fr']);

    $response->assertCreated();
    expect($response->json('message'))->toBeString();
});

it('supports multilingue descriptions', function (): void {
    $response = actingAs($this->professionalUser)
        ->postJson('/api/v1/companies', [
            'company_name' => 'Test Company',
            'cfe_number'   => 'CFE-2025-001',
            'description'  => [
                'en' => 'English description',
                'fr' => 'Description française',
            ],
        ]);

    $response->assertCreated();

    $company = Company::latest()->first();
    expect($company->getTranslation('description', 'en'))->toBe('English description');
    expect($company->getTranslation('description', 'fr'))->toBe('Description française');
});
