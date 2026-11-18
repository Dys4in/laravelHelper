<?php

namespace App\Http\Requests\Auth;

use App\Services\AuditLogger;
use App\Support\LoginThrottle;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
     * Con 5 contraseñas incorrectas seguidas el correo queda bloqueado 30 minutos (ver LoginThrottle).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $email = (string) $this->input('email');

        // Bloqueado: ni siquiera se comprueba la contraseña (así tampoco sirve la correcta).
        if (($seconds = LoginThrottle::lockedFor($email)) > 0) {
            throw $this->lockedException($seconds);
        }

        if (! Auth::attempt($this->only('email', 'password'))) {
            $left = LoginThrottle::hit($email);

            if ($left === 0) {
                event(new Lockout($this));

                AuditLogger::log(
                    'auth.lockout',
                    'Acceso bloqueado '.LoginThrottle::LOCK_MINUTES.' minutos por '.LoginThrottle::MAX_ATTEMPTS.' contraseñas incorrectas: '.$email,
                    actor: null,
                );

                throw $this->lockedException(LoginThrottle::LOCK_MINUTES * 60);
            }

            $message = 'Correo o contraseña incorrectos.';

            if ($left <= 2) {
                $message .= $left === 1
                    ? ' Te queda 1 intento antes de que el acceso se bloquee por '.LoginThrottle::LOCK_MINUTES.' minutos.'
                    : " Te quedan {$left} intentos antes de que el acceso se bloquee por ".LoginThrottle::LOCK_MINUTES.' minutos.';
            }

            throw ValidationException::withMessages(['email' => $message]);
        }

        // Las credenciales son correctas: ahora comprobamos si la cuenta está habilitada.
        $user = Auth::user();

        if ($reason = $user->accessDeniedReason()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $reason,
            ]);
        }

        LoginThrottle::clear($email);
    }

    private function lockedException(int $seconds): ValidationException
    {
        return ValidationException::withMessages([
            'email' => 'Demasiados intentos fallidos. Por seguridad, el acceso con este correo está bloqueado. '
                .'Vuelve a intentarlo en '.LoginThrottle::humanize($seconds).'.',
        ]);
    }
}
