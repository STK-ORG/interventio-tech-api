<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Post API Routes V1
|--------------------------------------------------------------------------
*/

// Routes publiques - Tout le monde peut voir les posts
Route::apiResource('posts', PostController::class)
    ->only(['index', 'show'])
    ->parameters(['posts' => 'post:slug']);

// Routes protégées - Seuls les utilisateurs authentifiés peuvent créer/modifier/supprimer
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('posts', PostController::class)
        ->except(['index', 'show'])
        ->parameters(['posts' => 'post:slug']);
});
