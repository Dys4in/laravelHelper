<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Rutas sencillas que antes eran closures en routes/web.php.
 * Con controladores, `php artisan route:cache` funciona y la app arranca más rápido.
 */
class HomeController extends Controller
{
    public function root(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }

    /** Punto de entrada único: el admin va a su dashboard; coordinador y usuario, a "Mis documentos". */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route(auth()->user()->role->dashboardRoute());
    }

    /** Aviso de actividad del navegador (ver <x-idle-timeout>). El middleware de inactividad ya anotó la hora. */
    public function ping(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
