<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

/**
 * Plantillas:
 *  - Todos los usuarios ven las de su área para crear documentos con "Usar plantilla".
 *  - Todos los usuarios suben plantillas para sus áreas (el coordinador puede tener varias).
 *  - Todos los usuarios administran las plantillas de sus áreas; el administrador controla todas (y las generales).
 */
class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->areaIds() !== [];
    }

    public function manage(User $user, Template $template): bool
    {
        return $user->isAdmin() || $user->hasArea($template->area_id);
    }
}
