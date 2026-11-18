<?php

use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\NoCache;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Endpoints del Document Server de OnlyOffice (sin sesión ni CSRF)
            Route::middleware('api')
                ->prefix('onlyoffice')
                ->name('onlyoffice.')
                ->group(base_path('routes/onlyoffice.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cierre de sesión por inactividad (después de StartSession, que ya va dentro del grupo "web").
        $middleware->web(append: [EnforceIdleTimeout::class]);

        $middleware->alias([
            'no.cache' => NoCache::class,
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
