<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/** Applies to user-selected passwords; temporary provisioning secrets are exempt until first login. */
class CorporatePassword implements ValidationRule
{
    public function __construct(private ?User $user = null, private ?string $name = null, private ?string $email = null, private ?string $role = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;
        $role = $this->role ?? $this->user?->role?->value ?? 'user';
        $min = in_array($role, ['admin', 'jefe_de_area', 'mod'], true) ? 10 : 8;
        if (mb_strlen($password) < $min) $fail("La contraseña debe tener al menos {$min} caracteres.");
        if (! preg_match('/\p{Lu}/u', $password) || ! preg_match('/\p{Ll}/u', $password)
            || ! preg_match('/\p{N}/u', $password) || ! preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
            $fail('La contraseña requiere mayúsculas, minúsculas, números y un símbolo.');
        }
        if (preg_match('/\s/u', $password)) $fail('La contraseña no puede contener espacios.');

        $lower = Str::lower($password);
        $names = [$this->name ?? $this->user?->name, $this->user?->username,
                  $this->email ?? $this->user?->email];
        foreach ($names as $name) {
            if (! $name) continue;
            // Compare all 3-character substrings of personal identifiers, excluding email domain.
            $name = Str::lower((string) $name);
            if (str_contains($name, '@')) $name = explode('@', $name)[0];
            foreach (preg_split('/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) as $token) {
                for ($i = 0; $i <= mb_strlen($token)-3; $i++) {
                    if (str_contains($lower, mb_substr($token, $i, 3))) {
                        $fail('No incluyas secuencias de 3 letras o números de tu nombre, usuario o correo.');
                        return;
                    }
                }
            }
        }
        // Other family names and personal data unknown to the system cannot be automatically verified.
    }
}
