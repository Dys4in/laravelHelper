<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

/**
 * Plantillas:
 *  - Todos los usuarios ven las de su área para crear documentos con "Usar plantilla".
 *  - El coordinador sube y administra las de SUS áreas (puede tener varias).
 *  - El administrador controla las de todas las áreas (y las generales, sin área).
 */
class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->hasRole('mod') && $user->areaIds() !== []);
    }

    public function manage(User $user, Template $template): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('mod') && $user->hasArea($template->area_id));
    }
}
