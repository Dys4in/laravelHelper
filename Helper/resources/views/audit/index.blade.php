<x-panel-layout title="Audit trail">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <p class="text-sm text-gray-600">
        Registro de todos los movimientos de la aplicación. Es de solo lectura: nadie puede editar ni borrar estos registros.
      </p>
    </div>
  </section>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('audit.index') }}" data-autosubmit class="hp-filterbar hp-filterbar--audit">
    <div class="hp-filter-row hp-filter-row--top">
      <div class="hp-search" role="search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por el usuario, detalle o área..."
               aria-label="Buscar en el registro de auditoría" autocomplete="off">
      </div>

      <select name="module" class="hp-select">
        <option value="">Todos los módulos</option>
        @foreach ($modules as $key => $label)
          <option value="{{ $key }}" @selected($filters['module'] === $key)>{{ $label }}</option>
        @endforeach
      </select>

      <select name="user" class="hp-select">
        <option value="">Todos los usuarios</option>
        @foreach ($actors as $actor)
          <option value="{{ $actor->id }}" @selected((string) $filters['user'] === (string) $actor->id)>{{ $actor->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="hp-filter-row hp-filter-row--bottom">
      <select name="area" class="hp-select">
        <option value="">Todas las áreas</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected((string) $filters['area'] === (string) $area->id)>{{ $area->name }}</option>
        @endforeach
      </select>

      <div class="hp-filter-dates">
        <label for="from" class="text-xs font-medium text-gray-500">Desde</label>
        <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="hp-input min-w-0 flex-1">
      </div>
      <div class="hp-filter-dates">
        <label for="to" class="text-xs font-medium text-gray-500">Hasta</label>
        <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="hp-input min-w-0 flex-1">
      </div>

      <div class="hp-filter-clear">
        <a href="{{ route('audit.index') }}" class="hp-btn-outline">Limpiar</a>
      </div>
    </div>
  </form>

  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Fecha</th>
          <th class="px-4 py-3">Usuario</th>
          <th class="px-4 py-3">Evento</th>
          <th class="px-4 py-3">Detalle</th>
          <th class="px-4 py-3">Área</th>
        </tr>
      </thead>
      <tbody id="audit-body" class="divide-y divide-gray-100">
        @forelse ($logs as $log)
          @include('audit._row', ['log' => $log])
        @empty
          <tr id="audit-empty"><td colspan="5" class="px-4 py-12 text-center text-gray-500">No hay movimientos que coincidan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $logs->links() }}
</x-panel-layout>
