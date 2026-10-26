<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use Illuminate\View\View;

/** Tablero de documentos compartido por los tres dashboards; cambia solo lo que cada rol puede ver. */
trait BuildsDocumentBoard
{
    protected function documentBoard(string $title, string $subtitle): View
    {
        $user = auth()->user();

        $documents = Document::query()
            ->visibleTo($user)
            ->with(['user', 'area'])
            ->latest('updated_at')
            ->get();

        return view('documents.board', [
            'documents' => $documents,
            'boardTitle' => $title,
            'boardSubtitle' => $subtitle,
        ]);
    }
}
