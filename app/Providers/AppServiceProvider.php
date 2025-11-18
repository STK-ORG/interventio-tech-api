<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\Post;
use App\Observers\CompanyObserver;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer les observers
        Post::observe(PostObserver::class);
        Company::observe(CompanyObserver::class);
    }
}
