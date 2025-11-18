<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $isVerified = fake()->boolean(30);

        return [
            'user_id'      => User::factory(),
            'company_name' => fake()->company(),
            'cfe_number'   => 'CFE-' . fake()->year() . '-' . fake()->unique()->numberBetween(1000, 9999),
            'address'      => fake()->address(),
            'description'  => [
                'en' => fake()->paragraph(),
                'fr' => fake()->paragraph(),
            ],
            'is_verified' => $isVerified,
            'verified_at' => $isVerified ? fake()->dateTimeBetween('-6 months', 'now') : null,
        ];
    }

    /**
     * Indicate that the company is verified.
     */
    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the company is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
