<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePersonalPassword {
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        if ($user && $user->needsNewPassword() && ! $request->routeIs('password.first.*', 'logout')) {
            $message = $user->must_change_password
                ? 'Debes cambiar tu contraseña temporal.'
                : 'Tu contraseña ha caducado y debes cambiarla.';
            if ($request->expectsJson()) return response()->json(['message' => $message, 'redirect' => route('password.first.edit')], 409);
            return redirect()->route('password.first.edit');
        }
        return $next($request);
    }
}
