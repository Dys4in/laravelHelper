<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

/**
 * El moderador sube y administra las plantillas de SU área.
 * El administrador controla las de todas las áreas (y las generales, sin área).
 */
class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'mod');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->hasRole('mod') && $user->area_id !== null);
    }

    public function manage(User $user, Template $template): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('mod') && $user->area_id !== null && $template->area_id === $user->area_id);
    }
}
