<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        // Documentos creados por día (últimos 14 días, con ceros en los días vacíos)
        $from = now()->subDays(13)->startOfDay();
        $perDay = Document::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(range(0, 13))->map(function (int $i) use ($from, $perDay) {
            $date = $from->copy()->addDays($i);

            return [
                'label' => $date->format('d/m'),
                'total' => (int) ($perDay[$date->format('Y-m-d')] ?? 0),
            ];
        });

        return view('admin.dashboard', [
            'stats' => [
                'documents' => Document::count(),
                'drafts' => Document::where('status', Document::DRAFT)->count(),
                'completed' => Document::where('status', Document::COMPLETED)->count(),
                'users' => User::where('status', 'active')->count(),
                'usersTotal' => User::count(),
                'areas' => Area::where('status', Area::ACTIVE)->count(),
                'areasTotal' => Area::count(),
                'templates' => Template::where('status', Template::ACTIVE)->count(),
                'templatesTotal' => Template::count(),
            ],
            'days' => $days,
            'maxPerDay' => max(1, (int) $days->max('total')),
            'areas' => Area::query()
                ->withCount([
                    'documents',
                    'documents as completed_count' => fn ($q) => $q->where('status', Document::COMPLETED),
                    'users as users_active_count' => fn ($q) => $q->where('status', 'active'),
                ])
                ->orderBy('name')
                ->get(),
            'recentDocuments' => Document::query()->with(['user', 'area'])->latest()->limit(6)->get(),
            'recentActivity' => AuditLog::query()->with('area')->latest('id')->limit(8)->get(),
        ]);
    }
}
