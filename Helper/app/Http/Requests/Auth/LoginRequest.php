<?php

namespace App\Http\Requests\Auth;

use App\Support\Audit;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Valida credenciales y, además, que la cuenta pueda entrar
     * (usuario activo y, salvo admin, con un área activa).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            Audit::log('auth.login_failed', 'Intento de inicio de sesión fallido', meta: ['email' => (string) $this->input('email')]);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Las credenciales son correctas: ahora comprobamos si la cuenta está habilitada.
        $user = Auth::user();

        if ($reason = $user->accessDeniedReason()) {
            Audit::log('auth.login_blocked', 'Acceso denegado: '.$reason, actor: $user, meta: ['email' => $user->email]);

            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $reason,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        Audit::log('auth.lockout', 'Inicio de sesión bloqueado temporalmente por demasiados intentos', meta: ['email' => (string) $this->input('email')]);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
