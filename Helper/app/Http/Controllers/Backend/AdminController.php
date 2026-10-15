<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $documents = Document::query()
            ->latest('updated_at')
            ->get()
            ->map(fn ($doc) => [
                'id'     => $doc->id,
                'code'   => $doc->code,       // ej. "VIL-PLNT-007"
                'status' => $doc->status,     // 'draft' | 'completed'
                'edited' => 'Edited ' . $doc->updated_at->diffForHumans(),
            ]);

        return view('admin.dashboard', [
            'documents' => $documents,
            'limit'     => 20,                // límite del plan (Personal Workspace)
        ]);
    }
}