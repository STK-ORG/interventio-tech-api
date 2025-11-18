<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine if the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->isProfessional();
    }

    /**
     * Determine if the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine if the user can create companies.
     */
    public function create(User $user): bool
    {
        return $user->isProfessional();
    }

    /**
     * Determine if the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine if the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine if the user can restore the company.
     */
    public function restore(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }

    /**
     * Determine if the user can permanently delete the company.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $user->id === $company->user_id;
    }
}
