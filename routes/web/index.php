<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => response()->json([
    'status'  => 'ok',
    'message' => 'API is running',
    'laravel' => app()->version(),
]));
