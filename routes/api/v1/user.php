<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('', function (Request $request) {
            return $request->user();
        })->name('current');
    });
