<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CompanyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company API Routes V1
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::apiResource('companies', CompanyController::class)
    ->only(['index', 'show']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('companies', CompanyController::class)
        ->except(['index', 'show']);
});
