<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('', [UserController::class, 'current'])->name('current');
    });
