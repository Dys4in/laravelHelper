<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /** Abrir o descargar: el admin todo; los demás, solo documentos de su área. */
    public function view(User $user, Document $document): bool
    {
        return $user->isAdmin()
            || ($user->area_id !== null && $user->area_id === $document->area_id);
    }

    /** Editar: un documento completado queda de solo lectura para los usuarios; el moderador y el admin pueden seguir editándolo. */
    public function edit(User $user, Document $document): bool
    {
        if (! $this->view($user, $document)) {
            return false;
        }

        return $document->status !== 'completed' || $user->hasRole('admin', 'mod');
    }

    /** Regular: cambiar el estado del documento (completar / reabrir). */
    public function manage(User $user, Document $document): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('mod') && $user->area_id !== null && $user->area_id === $document->area_id);
    }
}
