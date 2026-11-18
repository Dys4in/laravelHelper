@php
  $isAdmin = auth()->user()->isAdmin();
  $areaNames = $areas->pluck('name')->join(', ');
@endphp
<x-panel-layout :title="$isAdmin ? 'Todos los documentos' : 'Documentos del área'">
  <section class="hp-overview">
    <div>
      <p class="hp-overview-sub">
        @if ($isAdmin)
          Supervisión de los documentos de todas las áreas. Un documento se puede descargar cuando se marca como completado.
        @else
          Supervisión de los documentos de {{ $areas->count() > 1 ? 'tus áreas' : 'tu área' }} ({{ $areaNames }}). Ábrelos, edítalos y márcalos como completados para que se puedan descargar.
        @endif
      </p>
    </div>
    <div class="hp-stats">
      <span class="hp-stat cursor-default"><span class="hp-stat-label">Resultados:</span><span class="hp-stat-value">{{ $documents->total() }}</span></span>
    </div>
  </section>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('supervision.index') }}" data-autosubmit class="hp-filterbar">
    <div class="hp-search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por código, título o autor..." aria-label="Buscar documentos" autocomplete="off">
    </div>

    <select name="author" class="hp-select">
      <option value="">Todos los autores</option>
      @foreach ($authors as $author)
        <option value="{{ $author->id }}" @selected(request('author') == $author->id)>{{ $author->name }}</option>
      @endforeach
    </select>

    <select name="status" class="hp-select">
      <option value="">Todos los estados</option>
      <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
      <option value="completed" @selected(request('status') === 'completed')>Completado</option>
    </select>

    @if ($isAdmin || $areas->count() > 1)
      <select name="area" class="hp-select">
        <option value="">Todas las áreas</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected(request('area') == $area->id)>{{ $area->name }}</option>
        @endforeach
      </select>
    @endif

    <div class="hp-filter-clear">
      <a href="{{ route('supervision.index') }}" class="hp-btn-outline">Limpiar</a>
    </div>
  </form>

  <section class="hp-docs-grid" aria-label="Documentos supervisados">
    @forelse ($documents as $doc)
      @include('documents._card', ['doc' => $doc, 'supervise' => true])
    @empty
      <div class="hp-empty md:col-span-2 xl:col-span-3">No hay documentos que coincidan con los filtros.</div>
    @endforelse
  </section>

  {{ $documents->links() }}
</x-panel-layout>
