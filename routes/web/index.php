<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Rediriger vers la documentation
Route::get('/', function () {
    return redirect('/documentation');
});
