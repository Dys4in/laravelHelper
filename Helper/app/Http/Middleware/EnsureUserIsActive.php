<?php

namespace App\Http\Middleware;

use App\Support\Audit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión en la siguiente petición si el admin desactivó al usuario
 * o suspendió/eliminó su área mientras estaba conectado.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($reason = $user->accessDeniedReason())) {
            Audit::log('auth.session_revoked', 'Sesión cerrada por el sistema: '.$reason, actor: $user);

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
