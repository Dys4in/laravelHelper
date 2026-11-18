<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Cookie (cifrada por Laravel) con el correo que se recuerda en este equipo. */
    private const REMEMBER_COOKIE = 'helper_remembered_email';

    private const REMEMBER_MINUTES = 60 * 24 * 30; // 30 días

    public function create(Request $request): View
    {
        $email = $request->cookie(self::REMEMBER_COOKIE);

        $view = view('auth.login', [
            'rememberedEmail' => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        ]);

        // El navegador cierra la sesión por inactividad y llega aquí con ?expired=1 (ver <x-idle-timeout>).
        if ($request->boolean('expired') && ! session()->has('status')) {
            session()->now('status', 'Tu sesión se cerró por '.EnforceIdleTimeout::limitMinutes().' minutos de inactividad. Inicia sesión de nuevo.');
        }

        return $view;
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Arranca el reloj de inactividad desde el inicio de sesión.
        $request->session()->put(EnforceIdleTimeout::SESSION_KEY, now()->getTimestamp());

        // "Recordarme": se guarda el correo (nunca la contraseña) para precargarlo la próxima vez.
        if ($request->boolean('remember')) {
            Cookie::queue(self::REMEMBER_COOKIE, $request->user()->email, self::REMEMBER_MINUTES);
        } else {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));
        }

        // El registro de "inició sesión" lo hace el listener del evento Login (AppServiceProvider).
        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // El navegador envía reason=idle cuando cierra la sesión por inactividad (queda así en el audit trail).
        if ($request->input('reason') === 'idle') {
            $request->attributes->set('idle_logout', true);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // La cookie del correo recordado se conserva a propósito: es lo que se precarga en el login.
        return redirect('/');
    }
}
