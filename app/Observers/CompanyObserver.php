<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

final class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        Log::info('Nouvelle entreprise créée', [
            'company_id'   => $company->id,
            'company_name' => $company->company_name,
            'user_id'      => $company->user_id,
        ]);
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Notification de vérification
        if ($company->wasChanged('is_verified') && $company->is_verified) {
            Log::info('Entreprise vérifiée', [
                'company_id'   => $company->id,
                'company_name' => $company->company_name,
                'verified_at'  => $company->verified_at,
            ]);

            // TODO: Déclencher un événement CompanyVerified
            // event(new CompanyVerified($company));
        }
    }

    /**
     * Handle the Company "deleting" event.
     */
    public function deleting(Company $company): void
    {
        // Supprimer les médias associés
        $company->clearMediaCollection('logo');
        $company->clearMediaCollection('documents');

        Log::info('Entreprise supprimée', [
            'company_id'   => $company->id,
            'company_name' => $company->company_name,
        ]);
    }
}
