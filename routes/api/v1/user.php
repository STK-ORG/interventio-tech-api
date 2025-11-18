<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('/me', [UserController::class, 'current'])->name('current');
        Route::put('/me', [UserController::class, 'update'])->name('update');
        Route::post('/me/avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('/me/avatar', [UserController::class, 'deleteAvatar'])->name('delete-avatar');
    });
