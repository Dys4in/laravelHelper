<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ModController extends Controller
{
    /** El inicio de este rol es "Mis documentos". */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('documents.index');
    }
}
