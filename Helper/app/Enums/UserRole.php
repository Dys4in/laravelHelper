<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case JefeArea = 'jefe_de_area';
    case Mod = 'mod';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::JefeArea => 'Jefe de área',
            self::Mod => 'Coordinador',
            self::User => 'Usuario',
        };
    }

    /** Ruta (nombrada) a la que aterriza cada rol después del login. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::JefeArea => 'admin.dashboard',
            self::Mod => 'documents.index',
            self::User => 'documents.index',
        };
    }
}
