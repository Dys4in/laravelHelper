<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Reglas de documentos:
 *  - Usuario:       ve y edita solo SUS documentos.
 *  - Coordinador:   ve y edita los de sus áreas (puede tener varias); puede completarlos / reabrirlos.
 *  - Administrador: ve todos; puede completarlos / reabrirlos.
 *  - Completado:    se abre solo en modo lectura, sin importar el usuario.
 *  - Descargar PDF: solo documentos COMPLETADOS, sin excepciones.
 *  - Descargar Word/Excel: en cualquier estado, para quien puede ver el documento.
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
     * Editar: el CREADOR o el coordinador de su área, mientras no esté completado.
     */
    public function edit(User $user, Document $document): bool
    {
        return $this->view($user, $document)
            && ! $document->isCompleted()
            && ($document->user_id === $user->id
                || ($user->hasRole('mod') && $user->hasArea($document->area_id)));
    }

    /** El modo revisión está desactivado: cualquier documento accesible se abre para editar o solo leer. */
    public function review(User $user, Document $document): bool
    {
        return false;
    }

    /** Marcar como completado / volver a En proceso. */
    public function changeStatus(User $user, Document $document): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('mod') && $user->hasArea($document->area_id));
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document) && $document->isCompleted();
    }

    public function downloadOriginal(User $user, Document $document): bool
    {
        return $this->view($user, $document);
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
