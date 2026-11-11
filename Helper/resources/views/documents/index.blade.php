@php
  $total = $documents->count();
  $drafts = $documents->where('status', 'draft')->count();
  $completed = $documents->where('status', 'completed')->count();
@endphp

<x-panel-layout title="Mis documentos">
  <section class="hp-overview">
    <div>
      <p class="hp-overview-sub">Los documentos que creaste a partir de una plantilla. Se pueden descargar cuando estén marcados como completados.</p>
    </div>

    <div class="hp-stats" role="group" aria-label="Filtrar por estado">
      <button type="button" class="hp-stat" data-filter="all" aria-pressed="false">
        <span class="hp-stat-label">Todos:</span><span class="hp-stat-value" data-count="all">{{ $total }}</span>
      </button>
      <button type="button" class="hp-stat" data-filter="draft" aria-pressed="false">
        <span class="hp-stat-label">Borradores:</span><span class="hp-stat-value" data-count="draft">{{ $drafts }}</span>
      </button>
      <button type="button" class="hp-stat" data-filter="completed" aria-pressed="false">
        <span class="hp-stat-label">Completados:</span><span class="hp-stat-value" data-count="completed">{{ $completed }}</span>
      </button>
    </div>
  </section>

  {{-- Buscador y, al otro lado, "Nuevo documento" (lleva a Plantillas) --}}
  <section class="flex flex-wrap items-center justify-between gap-3">
    <form class="hp-search hp-search--block" role="search" onsubmit="return false">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input id="docSearch" type="search" placeholder="Buscar por código o título..." aria-label="Buscar en mis documentos" autocomplete="off" class="h-11 px-4 rounded-xl">
    </form>

    @can('create', \App\Models\Document::class)
      <a class="hp-btn-outline" href="{{ route('templates.index') }}">
        <i class="bi bi-file-earmark-plus mr-2" aria-hidden="true"></i><span>Nuevo documento</span>
      </a>
    @endcan
  </section>

  <section class="hp-docs-grid" id="docsGrid" aria-label="Lista de documentos">
    @foreach ($documents as $doc)
      @include('documents._card', ['doc' => $doc])
    @endforeach

    <div class="hp-empty" data-empty @if($total > 0) hidden @endif>
      Aún no tienes documentos. Crea el primero con “Nuevo documento”.
    </div>

    <div class="hp-empty" data-no-results hidden>No se encontraron documentos para tu búsqueda.</div>
  </section>
</x-panel-layout>
