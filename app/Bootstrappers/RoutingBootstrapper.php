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
            ->prefix('api')
            ->group(base_path(path: 'routes/api/routes.php'));

        $router->middleware('web')
            ->group(base_path(path: 'routes/web/routes.php'));

        $router->middleware('web')
            ->group(base_path(path: 'routes/console/routes.php'));

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

        if (file_exists($path = base_path('routes/channels/routes.php'))) {
            require $path;
        }

    }
}
