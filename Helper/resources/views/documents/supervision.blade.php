@php $isAdmin = auth()->user()->isAdmin(); @endphp
<x-panel-layout :title="$isAdmin ? 'Todos los documentos' : 'Documentos del área'">
  <section class="hp-overview">
    <div>
      <p class="hp-overview-sub">
        @if ($isAdmin)
          Supervisión de los documentos de todas las áreas. Un documento se puede descargar cuando se marca como completado.
        @else
          Supervisión de los documentos de tu área ({{ auth()->user()->area?->name }}). Ábrelos, edítalos y márcalos como completados para que se puedan descargar.
        @endif
      </p>
    </div>
    <div class="hp-stats">
      <span class="hp-stat cursor-default"><span class="hp-stat-label">Resultados:</span><span class="hp-stat-value">{{ $documents->total() }}</span></span>
    </div>
  </section>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('supervision.index') }}" data-autosubmit class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 {{ $isAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">
    <div class="hp-search sm:col-span-2 lg:col-span-1">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por código, título o autor..." aria-label="Buscar documentos" autocomplete="off">
    </div>

    <select name="author" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los autores</option>
      @foreach ($authors as $author)
        <option value="{{ $author->id }}" @selected(request('author') == $author->id)>{{ $author->name }}</option>
      @endforeach
    </select>

    <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los estados</option>
      <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
      <option value="completed" @selected(request('status') === 'completed')>Completado</option>
    </select>

    @if ($isAdmin)
      <select name="area" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <option value="">Todas las áreas</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected(request('area') == $area->id)>{{ $area->name }}</option>
        @endforeach
      </select>
    @endif

    <div class="flex gap-2">
      <button type="submit" class="hp-btn-outline flex-1">Filtrar</button>
      <a href="{{ route('supervision.index') }}" class="hp-btn-outline flex-1">Limpiar</a>
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
