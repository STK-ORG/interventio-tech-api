<?php

declare(strict_types=1);

namespace App\Bootstrappers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;

final class RoutingBootstrapper
{
    public function __invoke(Router $router): void
    {
        RateLimiter::for('api', fn(Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        $router->middleware('api')
            ->group(base_path(path: 'routes/api/index.php'));

        // Route racine simple (sans middleware web pour éviter les problèmes de session)
        $router->get('/', function (): \Illuminate\Http\JsonResponse {
            return \Illuminate\Support\Facades\Response::json([
                'status'  => 'ok',
                'message' => 'API is running',
            ]);
        });

        $router->middleware('web')
            ->group(base_path(path: 'routes/web/index.php'));

        $router->middleware('web')
            ->group(base_path(path: 'routes/console/index.php'));

        $router->middleware('web')
            ->get('up', function () {
                Event::dispatch(event: new DiagnosingHealth());

                return View::file(
                    path: base_path(
                        path: '/vendor/laravel/framework/src/Illuminate/Foundation/resources/health-up.blade.php',
                    ),
                );
            });

        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        if (file_exists($path = base_path('routes/channels/index.php'))) {
            require $path;
        }
    }
}
