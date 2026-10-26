<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\BuildsDocumentBoard;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    use BuildsDocumentBoard;

    public function dashboard()
    {
        return $this->documentBoard(
            'Todos los documentos',
            'Supervisa los documentos de todas las áreas. Cada movimiento queda en la Auditoría.'
        );
    }
}
