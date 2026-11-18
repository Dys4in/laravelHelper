<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit trail: todos los usuarios ven todos los movimientos.
 * Solo lectura: los registros no se pueden editar ni eliminar.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $logs = AuditLog::query()
            ->with('area')
            ->filtered($filters)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'modules' => AuditLog::MODULES,
            'actors' => User::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'areas' => Area::withTrashed()->orderBy('name')->get(),
        ]);
    }

    /** @return array{q:?string,module:?string,user:?string,area:?string,from:?string,to:?string} */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'in:'.implode(',', array_keys(AuditLog::MODULES))],
            'user' => ['nullable', 'integer'],
            'area' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return [
            'q' => $data['q'] ?? null,
            'module' => $data['module'] ?? null,
            'user' => $data['user'] ?? null,
            'area' => $data['area'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ];
    }
}
