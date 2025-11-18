<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Creates a new user and authenticates it.
 */
function authenticateUser(): void
{
    // Create a new user
    /** @var User $user */
    $user = User::factory()->create();

    // Authenticate the user
    actingAs(user: $user);
}
