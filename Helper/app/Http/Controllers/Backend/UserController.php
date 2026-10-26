<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\BuildsDocumentBoard;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use BuildsDocumentBoard;

    public function dashboard()
    {
        return $this->documentBoard(
            'Mis documentos',
            'Crea documentos desde las plantillas de tu área y edítalos.'
        );
    }
}
