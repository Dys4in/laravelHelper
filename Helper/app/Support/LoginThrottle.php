<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Bloqueo por contraseñas incorrectas: a la 5.ª equivocación seguida el acceso con ese correo
 * queda bloqueado 30 minutos (aunque después se escriba la contraseña correcta).
 *
 * El bloqueo es por correo y no por IP: cambiar de red no da más intentos.
 */
final class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_MINUTES = 30;

    private static function key(string $email): string
    {
        return 'login:'.sha1(Str::transliterate(Str::lower(trim($email))));
    }

    /** Segundos que faltan para que termine el bloqueo; 0 si el correo no está bloqueado. */
    public static function lockedFor(string $email): int
    {
        $until = Cache::get(self::key($email).':lock');

        return is_numeric($until) ? max(0, (int) $until - now()->getTimestamp()) : 0;
    }

    /**
     * Anota una contraseña incorrecta.
     * Los fallos se cuentan dentro de una ventana de 30 minutos desde el primero.
     *
     * @return int Intentos que quedan antes del bloqueo (0 = se acaba de bloquear).
     */
    public static function hit(string $email): int
    {
        $key = self::key($email);
        $window = self::LOCK_MINUTES * 60;

        $attempts = RateLimiter::hit($key, $window);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put($key.':lock', now()->getTimestamp() + $window, $window);
            RateLimiter::clear($key);

            return 0;
        }

        return self::MAX_ATTEMPTS - $attempts;
    }

    /** Inicio de sesión correcto (o desbloqueo manual): se borra el conteo y el bloqueo. */
    public static function clear(string $email): void
    {
        $key = self::key($email);

        RateLimiter::clear($key);
        Cache::forget($key.':lock');
    }

    /** "29 minutos", "1 minuto"... para mostrar en pantalla. */
    public static function humanize(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return $minutes === 1 ? '1 minuto' : "{$minutes} minutos";
    }
}
