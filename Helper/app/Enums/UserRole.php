<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Mod = 'mod';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Mod => 'Moderador',
            self::User => 'Usuario',
        };
    }

    /** Ruta (nombrada) a la que aterriza cada rol después del login. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Mod => 'mod.dashboard',
            self::User => 'user.dashboard',
        };
    }
}
