<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Route racine - retourner une réponse JSON simple
Route::get('/', function (): \Illuminate\Http\JsonResponse {
    return \Illuminate\Support\Facades\Response::json([
        'status'  => 'ok',
        'message' => 'API is running',
    ]);
});
