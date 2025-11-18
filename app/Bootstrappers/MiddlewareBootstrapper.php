<?php

declare(strict_types=1);

namespace App\Bootstrappers;

use App\Http\Middleware\Localize;
use Illuminate\Foundation\Configuration\Middleware;

final class MiddlewareBootstrapper
{
    public function __invoke(Middleware $middleware): void
    {
        $middleware->alias([]);

        $middleware->api(prepend: [
            Localize::class,
        ]);
    }
}
