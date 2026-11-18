<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

/**
 * Plantillas:
 *  - Todos los usuarios ven las de su área para crear documentos con "Usar plantilla".
 *  - El moderador sube y administra las de SU área.
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
        return $user->isAdmin() || ($user->hasRole('mod') && count($user->assignedAreaIds()) > 0);
    }

    public function manage(User $user, Template $template): bool
    {
        return $user->isAdmin()
            || $user->canManageArea($template->area_id);
    }
}
