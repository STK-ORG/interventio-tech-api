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

        // Faire confiance à tous les proxies (Render, Cloudflare, etc.)
        // En production, Laravel doit détecter correctement le protocole HTTPS
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB);
    }
}
