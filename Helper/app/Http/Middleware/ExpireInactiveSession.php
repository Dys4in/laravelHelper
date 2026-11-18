<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpireInactiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $last = (int) $request->session()->get('helper_last_activity', time());
            if (time() - $last >= 15 * 60) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'La sesión se cerró por 15 minutos de inactividad.',
                ]);
            }
            $request->session()->put('helper_last_activity', time());
        }

        return $next($request);
    }
}
