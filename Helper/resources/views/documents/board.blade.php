@php
  $me = auth()->user();
  $total = $documents->count();
  $drafts = $documents->where('status', 'draft')->count();
  $completed = $documents->where('status', 'completed')->count();
  $showOwner = ! $me->hasRole('user');
@endphp

<x-admin-layout :title="$boardTitle">
  <section class="hp-overview">
    <div>
      <h2 class="hp-overview-title">{{ $boardTitle }}</h2>
      <p class="hp-overview-sub">{{ $boardSubtitle }}</p>
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

  <section class="flex flex-wrap items-center gap-3">
    <form class="hp-search" role="search" onsubmit="return false">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input id="docSearch" type="search" placeholder="Buscar por código, título o persona..." aria-label="Buscar documentos" autocomplete="off">
    </form>

    @can('create', \App\Models\Document::class)
      <a class="hp-btn-primary" href="{{ route('documents.create') }}">
        <i class="bi bi-file-earmark-plus" aria-hidden="true"></i><span>Nuevo documento</span>
      </a>
    @endcan
  </section>

  <section class="hp-docs-grid" id="docsGrid" aria-label="Lista de documentos">
    @foreach ($documents as $doc)
      @php
        $search = implode(' ', array_filter([$doc->code, $doc->title, $doc->user?->name, $doc->area?->name, $doc->template_name]));
        $next = $doc->status === 'completed' ? 'draft' : 'completed';
      @endphp

      <article class="hp-card" data-doc data-id="{{ $doc->id }}" data-code="{{ $search }}" data-status="{{ $doc->status }}">
        <div class="hp-card-body">
          <div class="hp-card-row">
            <span class="hp-badge hp-badge--{{ $doc->status }}" data-badge>{{ $doc->status }}</span>
            <i class="bi bi-file-earmark-word text-xl text-brand" aria-hidden="true"></i>
          </div>

          <div class="hp-card-details">
            <p class="hp-card-code">{{ $doc->code }}</p>
            <p class="truncate text-sm font-medium text-gray-800">{{ $doc->title }}</p>
            @if ($showOwner)
              <p class="truncate text-xs text-gray-500">
                <i class="bi bi-person" aria-hidden="true"></i> {{ $doc->user?->name ?? 'Usuario eliminado' }}
                @if ($me->isAdmin()) · {{ $doc->area?->name ?? 'Sin área' }} @endif
              </p>
            @endif
            <p class="hp-card-date">Editado {{ $doc->updated_at->locale('es')->diffForHumans() }}</p>
          </div>

          <a class="hp-btn-outline" href="{{ route('documents.edit', $doc) }}">Abrir en el editor</a>

          <div class="flex flex-wrap items-center gap-2">
            @can('changeStatus', $doc)
              <form method="POST" action="{{ route('documents.status', $doc) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $next }}">
                <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
                  {{ $next === 'completed' ? 'Marcar completado' : 'Volver a borrador' }}
                </button>
              </form>
            @endcan

            <a href="{{ route('documents.download', $doc) }}" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
              <i class="bi bi-download" aria-hidden="true"></i> .docx
            </a>

            @can('delete', $doc)
              <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="ml-auto"
                    data-confirm="Se eliminará «{{ $doc->title }}» ({{ $doc->code }}) y su archivo. Esta acción no se puede deshacer."
                    data-confirm-title="Eliminar documento" data-confirm-text="Eliminar" data-confirm-variant="danger">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
              </form>
            @endcan
          </div>
        </div>
      </article>
    @endforeach

    <div class="hp-empty" data-empty @if($total > 0) hidden @endif>
      @if ($me->hasRole('user'))
        Aún no tienes documentos. Crea el primero con “Nuevo documento”.
      @else
        Todavía no hay documentos {{ $me->isAdmin() ? '' : 'en tu área' }}.
      @endif
    </div>

    <div class="hp-empty" data-no-results hidden>No se encontraron documentos para tu búsqueda.</div>
  </section>
</x-admin-layout>
