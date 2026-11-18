<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión tras N minutos sin actividad (config session.idle_minutes, 15 por defecto).
 *
 * "Actividad" = peticiones que hace la persona (navegar, enviar formularios, el aviso periódico
 * /session/ping del navegador mientras interactúa). Las consultas automáticas de fondo, como el
 * "En vivo" del audit trail, NO cuentan: si no, una pantalla abandonada nunca caducaría.
 */
class EnforceIdleTimeout
{
    public const SESSION_KEY = 'last_activity_at';

    /**
     * El navegador avisa de su actividad como máximo cada ~60 s y cierra él mismo a los N minutos exactos.
     * El servidor espera ese margen extra para no cerrar ANTES que la pantalla; sirve de respaldo
     * cuando el navegador ya no está (pestaña cerrada, equipo apagado).
     */
    private const GRACE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $limit = self::limitSeconds() + self::GRACE_SECONDS;
        $now = now()->getTimestamp();
        $last = (int) $request->session()->get(self::SESSION_KEY, $now);

        if ($now - $last > $limit) {
            // Lo lee el listener de Logout (AppServiceProvider) para registrar "por inactividad".
            $request->attributes->set('idle_logout', true);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Tu sesión se cerró por '.self::limitMinutes().' minutos de inactividad. Inicia sesión de nuevo.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'redirect' => route('login')], 401);
            }

            return redirect()->route('login')->with('status', $message);
        }

        if (! $this->isBackgroundRequest($request)) {
            $request->session()->put(self::SESSION_KEY, $now);
        }

        return $next($request);
    }

    public static function limitMinutes(): int
    {
        return max(1, (int) config('session.idle_minutes', 15));
    }

    public static function limitSeconds(): int
    {
        return self::limitMinutes() * 60;
    }

    /** Peticiones automáticas que no demuestran que haya alguien frente a la pantalla. */
    private function isBackgroundRequest(Request $request): bool
    {
        return $request->routeIs('audit.feed') || $request->headers->has('X-Background-Request');
    }
}
