<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\BuildsDocumentBoard;
use App\Http\Controllers\Controller;

class ModController extends Controller
{
    use BuildsDocumentBoard;

    public function dashboard()
    {
        $area = auth()->user()->area?->name ?? 'tu área';

        return $this->documentBoard(
            'Documentos de '.$area,
            'Revisa, edita y regula los documentos de los usuarios de tu área.'
        );
    }
}
