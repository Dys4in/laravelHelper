<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Reglas de documentos:
 *  - Usuario:       ve y edita solo SUS documentos.
 *  - Coordinador:   ve los de sus áreas (puede tener varias); los de otros los REVISA (control de cambios); puede completarlos / reabrirlos.
 *  - Administrador: ve todos; los de otros los REVISA; puede completarlos / reabrirlos.
 *  - Completado:    se abre solo en modo lectura, sin importar el usuario.
 *  - Descargar:     solo documentos COMPLETADOS, sin excepciones.
 *  - Renombrar:     el dueño, el coordinador de su área o el admin, en cualquier estado.
 *  - Eliminar:      el dueño (usuario o coordinador) en cualquier estado; el admin siempre.
 */
class DocumentPolicy
{
    /** Cualquiera con acceso a la app puede crear documentos (el admin elige el área al crearlo). */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->areaIds() !== [];
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->isAdmin() || $document->user_id === $user->id) {
            return true;
        }

        return $user->hasRole('mod') && $user->hasArea($document->area_id);
    }

    /**
     * Editar: solo el CREADOR del documento y mientras no esté completado.
     * Coordinador y administrador que no son el creador no editan: revisan (ver review()).
     */
    public function edit(User $user, Document $document): bool
    {
        return $this->view($user, $document)
            && ! $document->isCompleted()
            && $document->user_id === $user->id;
    }

    /**
     * Revisar (control de cambios): coordinador o administrador que NO son el creador, mientras no esté completado.
     * Sus cambios quedan como sugerencias que el creador acepta o rechaza.
     */
    public function review(User $user, Document $document): bool
    {
        return $this->view($user, $document)
            && ! $document->isCompleted()
            && $document->user_id !== $user->id
            && $user->hasRole('admin', 'mod');
    }

    /** Marcar como completado / volver a borrador. */
    public function changeStatus(User $user, Document $document): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('mod') && $user->hasArea($document->area_id));
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document) && $document->isCompleted();
    }

    /** Renombrar no toca el contenido: puede hacerlo quien puede ver el documento, incluso completado. */
    public function rename(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin() || $document->user_id === $user->id;
    }
}
