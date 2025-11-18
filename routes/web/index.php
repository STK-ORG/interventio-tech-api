<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Route racine - retourner une réponse JSON avec lien vers la documentation
Route::get('/', function () {
    return response()->json([
        'status'  => 'ok',
        'message' => 'API is running',
        'laravel' => app()->version(),
        'documentation' => url('/documentation'),
        'api' => url('/v1'),
    ]);
});
