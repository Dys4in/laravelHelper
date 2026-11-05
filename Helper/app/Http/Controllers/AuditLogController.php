<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
            'lastId' => (int) ($logs->first()?->id ?? 0),
            'firstPage' => $logs->currentPage() === 1,
        ]);
    }

    /**
     * Movimientos nuevos desde el id indicado, con los mismos filtros de la pantalla.
     * La pantalla lo consulta cada pocos segundos para mostrarlos en vivo.
     */
    public function feed(Request $request): JsonResponse
    {
        $after = max(0, (int) $request->input('after'));

        $logs = AuditLog::query()
            ->with('area')
            ->filtered($this->filters($request))
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(50)
            ->get();

        $html = $logs->sortByDesc('id')
            ->map(fn (AuditLog $log) => view('audit._row', ['log' => $log])->render())
            ->implode('');

        return response()->json([
            'count' => $logs->count(),
            'last_id' => (int) ($logs->max('id') ?? $after),
            'html' => $html,
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
