<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Company;
use App\Models\Post;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Utilisateur privé
        $privateUser = User::factory()->create([
            'name'         => 'John Doe',
            'email'        => 'john@example.com',
            'account_type' => AccountType::PRIVATE,
            'address'      => '123 Main Street, Lomé',
        ]);

        // Utilisateur professionnel
        $proUser = User::factory()->create([
            'name'         => 'Jane Smith',
            'email'        => 'jane@example.com',
            'account_type' => AccountType::PROFESSIONAL,
            'address'      => '456 Business Ave, Lomé',
        ]);

        // Profil professionnel
        ProfessionalProfile::factory()->create([
            'user_id'      => $proUser->id,
            'company_name' => 'Tech Solutions SARL',
            'cfe_number'   => 'CFE-2024-001',
        ]);

        // Entreprises additionnelles
        Company::factory()->create([
            'user_id'      => $proUser->id,
            'company_name' => 'Digital Services',
            'cfe_number'   => 'CFE-2024-002',
            'address'      => 'Lomé, Togo',
            'description'  => [
                'en' => 'Digital services company',
                'fr' => 'Entreprise de services numériques',
            ],
        ]);

        Company::factory()->create([
            'user_id'      => $proUser->id,
            'company_name' => 'Innovation Hub',
            'cfe_number'   => 'CFE-2024-003',
            'address'      => 'Lomé, Togo',
            'description'  => [
                'en' => 'Innovation and technology hub',
                'fr' => 'Centre d\'innovation et de technologie',
            ],
        ]);

        // Posts de l'utilisateur privé
        Post::factory()->published()->create([
            'user_id' => $privateUser->id,
            'title'   => [
                'en' => 'My First Post',
                'fr' => 'Mon Premier Article',
            ],
            'content' => [
                'en' => 'This is my first publication in English',
                'fr' => 'Ceci est ma première publication en français',
            ],
        ]);

        // Posts de l'utilisateur professionnel
        Post::factory()->published()->create([
            'user_id' => $proUser->id,
            'title'   => [
                'en' => 'Company Announcement',
                'fr' => 'Annonce de l\'Entreprise',
            ],
            'content' => [
                'en' => 'We are launching a new service',
                'fr' => 'Nous lançons un nouveau service',
            ],
        ]);

        Post::factory()->draft()->create([
            'user_id' => $proUser->id,
            'title'   => [
                'en' => 'Draft Article',
                'fr' => 'Brouillon d\'Article',
            ],
            'content' => [
                'en' => 'This is a draft article',
                'fr' => 'Ceci est un brouillon d\'article',
            ],
        ]);

        // Créer des utilisateurs et posts additionnels pour les tests
        User::factory(5)
            ->has(Post::factory()->published()->count(3))
            ->create(['account_type' => AccountType::PRIVATE]);

        User::factory(3)
            ->has(ProfessionalProfile::factory())
            ->has(Company::factory()->count(2))
            ->has(Post::factory()->published()->count(5))
            ->create(['account_type' => AccountType::PROFESSIONAL]);
    }
}
