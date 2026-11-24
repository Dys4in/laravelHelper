<?php

use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\NoCache;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\RequirePersonalPassword;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $middleware->web(append: [EnforceIdleTimeout::class, RequirePersonalPassword::class]);

        $middleware->alias([
            'no.cache' => NoCache::class,
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || ! $exception->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            $message = 'Tu sesión se renovó. Vuelve a intentarlo.';
            $fallback = $request->user() ? route('dashboard') : route('login');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'redirect' => $fallback], 419);
            }

            $previous = url()->previous();
            $target = $fallback;

            if ($previous && parse_url($previous, PHP_URL_HOST) === $request->getHost()) {
                try {
                    Route::getRoutes()->match(Request::create($previous, 'GET'));
                    $target = $previous;
                } catch (NotFoundHttpException | MethodNotAllowedHttpException $e) {
                    $target = $fallback;
                }
            }

            return redirect()->to($target)->with('warning', $message);
        });
    })->create();
