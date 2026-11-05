@php
  $pct = fn (int $part, int $whole) => $whole > 0 ? round($part / $whole * 100) : 0;
@endphp

<x-panel-layout title="Dashboard">
  <section class="hp-overview">
    <div>
      <h2 class="hp-overview-title">Dashboard</h2>
      <p class="hp-overview-sub">Resumen de la actividad de todas las áreas.</p>
    </div>
    <a href="{{ route('audit.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
      <i class="bi bi-shield-check" aria-hidden="true"></i> Ver auditoría completa
    </a>
  </section>

  {{-- Indicadores --}}
  <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
    @foreach ([
      ['Documentos', $stats['documents'], 'bi-file-earmark-text', 'bg-brand-soft text-brand', route('supervision.index'), 'Ver todos'],
      ['Borradores', $stats['drafts'], 'bi-pencil-square', 'bg-gray-100 text-gray-600', route('supervision.index', ['status' => 'draft']), 'Pendientes de completar'],
      ['Completados', $stats['completed'], 'bi-check2-circle', 'bg-success-bg text-success-text', route('supervision.index', ['status' => 'completed']), 'Listos para descargar'],
      ['Plantillas activas', $stats['templates'], 'bi-grid', 'bg-brand-soft text-brand', route('templates.index'), 'de '.$stats['templatesTotal'].' en total'],
    ] as [$label, $value, $ico, $tone, $href, $hint])
      <a href="{{ $href }}" class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $tone }}"><i class="bi {{ $ico }}" aria-hidden="true"></i></span>
        <span class="min-w-0">
          <span class="block text-sm text-gray-500">{{ $label }}</span>
          <span class="block text-2xl font-bold leading-tight text-gray-900">{{ $value }}</span>
          <span class="block truncate text-xs text-gray-400">{{ $hint }}</span>
        </span>
      </a>
    @endforeach
  </section>

  <section class="grid gap-4 sm:grid-cols-2">
    <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300">
      <span><span class="block text-sm text-gray-500">Usuarios activos</span><span class="text-2xl font-bold text-gray-900">{{ $stats['users'] }}</span> <span class="text-sm text-gray-400">de {{ $stats['usersTotal'] }}</span></span>
      <i class="bi bi-people text-2xl text-gray-300" aria-hidden="true"></i>
    </a>
    <a href="{{ route('admin.areas.index') }}" class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300">
      <span><span class="block text-sm text-gray-500">Áreas activas</span><span class="text-2xl font-bold text-gray-900">{{ $stats['areas'] }}</span> <span class="text-sm text-gray-400">de {{ $stats['areasTotal'] }}</span></span>
      <i class="bi bi-diagram-3 text-2xl text-gray-300" aria-hidden="true"></i>
    </a>
  </section>

  <section class="grid gap-6 xl:grid-cols-3">
    {{-- Documentos creados por día --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 xl:col-span-2">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="font-bold text-gray-900">Documentos creados · últimos 14 días</h3>
        <span class="text-sm text-gray-500">{{ $days->sum('total') }} en total</span>
      </div>

      <div class="flex h-44 items-end gap-1.5 sm:gap-2" role="img" aria-label="Gráfica de documentos creados por día">
        @foreach ($days as $day)
          <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $day['label'] }}: {{ $day['total'] }}">
            <span class="text-[11px] font-semibold text-gray-500 {{ $day['total'] ? '' : 'opacity-0' }}">{{ $day['total'] }}</span>
            <div class="w-full rounded-t-md {{ $day['total'] ? 'bg-brand' : 'bg-gray-100' }}"
                 style="height: {{ $day['total'] ? max(6, round($day['total'] / $maxPerDay * 100)) : 4 }}%"></div>
          </div>
        @endforeach
      </div>
      <div class="mt-2 flex gap-1.5 sm:gap-2">
        @foreach ($days as $day)
          <span class="min-w-0 flex-1 truncate text-center text-[10px] text-gray-400">{{ $day['label'] }}</span>
        @endforeach
      </div>
    </div>

    {{-- Por área --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5">
      <h3 class="mb-4 font-bold text-gray-900">Documentos por área</h3>
      <div class="space-y-4">
        @forelse ($areas as $area)
          <div>
            <div class="mb-1 flex items-center justify-between gap-2 text-sm">
              <span class="truncate font-medium text-gray-800">
                {{ $area->name }}
                @unless ($area->isActive())<span class="text-xs font-normal text-amber-600">(suspendida)</span>@endunless
              </span>
              <span class="shrink-0 text-gray-500">{{ $area->completed_count }}/{{ $area->documents_count }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $pct($area->completed_count, $area->documents_count) }}" aria-valuemin="0" aria-valuemax="100">
              <div class="h-full rounded-full bg-brand" style="width: {{ $pct($area->completed_count, $area->documents_count) }}%"></div>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ $area->users_active_count }} {{ $area->users_active_count === 1 ? 'usuario activo' : 'usuarios activos' }} · completados / total</p>
          </div>
        @empty
          <p class="text-sm text-gray-500">Aún no hay áreas.</p>
        @endforelse
      </div>
    </div>
  </section>

  <section class="grid gap-6 xl:grid-cols-2">
    {{-- Documentos recientes --}}
    <div class="rounded-xl border border-gray-200 bg-white">
      <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
        <h3 class="font-bold text-gray-900">Documentos recientes</h3>
        <a href="{{ route('supervision.index') }}" class="text-sm font-medium text-brand hover:underline">Ver todos</a>
      </div>
      <ul class="divide-y divide-gray-100">
        @forelse ($recentDocuments as $doc)
          <li>
            <a href="{{ route('documents.edit', $doc) }}" class="flex items-center justify-between gap-3 px-5 py-3 transition hover:bg-gray-50">
              <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-gray-900">{{ $doc->title }}</span>
                <span class="block truncate text-xs text-gray-500">{{ $doc->code }} · {{ $doc->user?->name ?? '—' }} · {{ $doc->area?->name ?? 'Sin área' }}</span>
              </span>
              <span class="flex shrink-0 flex-col items-end gap-1">
                <span class="hp-badge hp-badge--{{ $doc->status }}">{{ $doc->status }}</span>
                <span class="text-[11px] text-gray-400">{{ $doc->created_at->format('d/m/Y') }}</span>
              </span>
            </a>
          </li>
        @empty
          <li class="px-5 py-8 text-center text-sm text-gray-500">Aún no hay documentos.</li>
        @endforelse
      </ul>
    </div>

    {{-- Actividad reciente --}}
    <div class="rounded-xl border border-gray-200 bg-white">
      <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
        <h3 class="font-bold text-gray-900">Actividad reciente</h3>
        <a href="{{ route('audit.index') }}" class="text-sm font-medium text-brand hover:underline">Auditoría completa</a>
      </div>
      <ul class="divide-y divide-gray-100">
        @forelse ($recentActivity as $log)
          @php
            $dot = match (\App\Models\AuditLog::tone($log->event)) {
                'green' => 'bg-green-500', 'red' => 'bg-red-500', 'amber' => 'bg-amber-500', 'indigo' => 'bg-indigo-500', default => 'bg-gray-400',
            };
          @endphp
          <li class="flex items-start gap-3 px-5 py-3">
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm text-gray-900">{{ $log->description }}</span>
              <span class="block truncate text-xs text-gray-500">{{ $log->user_name ?? 'Sin sesión' }} · {{ \App\Models\AuditLog::eventLabel($log->event) }}</span>
            </span>
            <span class="shrink-0 text-[11px] text-gray-400">{{ $log->created_at->locale('es')->diffForHumans(short: true) }}</span>
          </li>
        @empty
          <li class="px-5 py-8 text-center text-sm text-gray-500">Sin movimientos todavía.</li>
        @endforelse
      </ul>
    </div>
  </section>
</x-panel-layout>
