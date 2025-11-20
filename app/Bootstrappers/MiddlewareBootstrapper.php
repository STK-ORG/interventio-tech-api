<?php

declare(strict_types=1);

namespace App\Bootstrappers;

use App\Http\Middleware\Localize;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

final class MiddlewareBootstrapper
{
    public function __invoke(Middleware $middleware): void
    {
        $middleware->alias([]);

        // Configurer CORS pour permettre les requêtes depuis Flutter
        // Le middleware HandleCors est automatiquement inclus dans Laravel 12
        // La configuration se fait via config/cors.php
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
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
