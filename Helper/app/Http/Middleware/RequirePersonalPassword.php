<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePersonalPassword {
    public function handle(Request $request, Closure $next): Response {
        if ($request->user()?->must_change_password && ! $request->routeIs('password.first.*', 'logout')) {
            if ($request->expectsJson()) return response()->json(['message' => 'Debes cambiar tu contraseña temporal.', 'redirect' => route('password.first.edit')], 409);
            return redirect()->route('password.first.edit');
        }
        return $next($request);
    }
}
