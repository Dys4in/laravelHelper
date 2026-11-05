<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        return view('auth.login', [
            'rememberedEmail' => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // La cookie del correo recordado se conserva a propósito: es lo que se precarga en el login.
        return redirect('/');
    }
}
