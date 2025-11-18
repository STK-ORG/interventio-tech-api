<?php

declare(strict_types=1);

use App\Bootstrappers\ExceptionsBootstrapper;
use App\Bootstrappers\MiddlewareBootstrapper;
use App\Bootstrappers\RoutingBootstrapper;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(path: __DIR__))
    ->withRouting(using: (new RoutingBootstrapper())(...))
    ->withMiddleware(callback: new MiddlewareBootstrapper())
    ->withExceptions(using: new ExceptionsBootstrapper())
    ->create();
