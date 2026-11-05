<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Cálculos que comparten los tres dashboards (usuario, moderador y administrador). */
trait BuildsDashboard
{
    /**
     * Conteo de documentos por estado sobre la consulta recibida.
     *
     * @return array{total:int,draft:int,completed:int}
     */
    protected function statusCounts(Builder $documents): array
    {
        $rows = (clone $documents)->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $draft = (int) ($rows['draft'] ?? 0);
        $completed = (int) ($rows['completed'] ?? 0);

        return ['total' => $draft + $completed, 'draft' => $draft, 'completed' => $completed];
    }

    /**
     * Documentos creados por mes (últimos $months meses, el actual incluido).
     * Se agrupa en PHP para no depender de funciones de fecha de un motor concreto.
     *
     * @return list<array{label:string,count:int}>
     */
    protected function monthlySeries(Builder $documents, int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $perMonth = (clone $documents)
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        $series = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);

            $series[] = [
                'label' => ucfirst(rtrim($month->copy()->locale('es')->isoFormat('MMM'), '.')),
                'count' => (int) ($perMonth[$month->format('Y-m')] ?? 0),
            ];
        }

        return $series;
    }

    /** Últimos movimientos del audit trail; con $areaId, solo los de esa área. */
    protected function recentActivity(?int $areaId = null, int $limit = 8): Collection
    {
        return AuditLog::query()
            ->when($areaId !== null, fn ($q) => $q->where('area_id', $areaId))
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
