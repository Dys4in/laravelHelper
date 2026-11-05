{{--
  Tarjeta de documento (compartida por «Mis documentos» y «Documentos»).
  Variables: $doc (obligatoria) · $supervise (opcional): muestra autor y área.
--}}
@php
  $supervise = $supervise ?? false;
  $done = $doc->isCompleted();
  $next = $done ? 'draft' : 'completed';
  $canEdit = auth()->user()->can('edit', $doc);
  $canReview = auth()->user()->can('review', $doc);
  $openLabel = $canEdit ? 'Abrir en el editor' : ($canReview ? 'Revisar documento' : 'Ver documento');
  $search = implode(' ', array_filter([$doc->code, $doc->title, $doc->user?->name, $doc->area?->name, $doc->template?->name]));
@endphp

<article class="hp-card" data-doc data-id="{{ $doc->id }}" data-code="{{ $search }}" data-status="{{ $doc->status }}">
  <div class="hp-card-body">
    <div class="hp-card-row">
      <span class="hp-badge hp-badge--{{ $doc->status }}" data-badge>{{ $doc->status }}</span>
      <span class="hp-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-word"></i></span>
    </div>

    <div class="hp-card-details">
      <p class="hp-card-title" title="{{ $doc->title }}">{{ $doc->title }}</p>
      <p class="truncate text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $doc->code }}</p>

      <ul class="hp-card-meta">
        <li>
          <i class="bi bi-person" aria-hidden="true"></i>
          <span class="truncate">{{ $doc->user?->name ?? 'Usuario eliminado' }}</span>
        </li>
        @if ($supervise)
          <li><i class="bi bi-building" aria-hidden="true"></i><span class="truncate">{{ $doc->area?->name ?? 'Sin área' }}</span></li>
        @endif
        @if ($doc->template)
          <li><i class="bi bi-grid" aria-hidden="true"></i><span class="truncate">{{ $doc->template->name }}</span></li>
        @endif
        <li>
          <i class="bi bi-calendar-plus" aria-hidden="true"></i>
          <span>Creado <time datetime="{{ $doc->created_at->toIso8601String() }}" title="{{ $doc->created_at->format('d/m/Y H:i') }}">{{ $doc->created_at->format('d/m/Y') }}</time></span>
        </li>
        <li>
          <i class="bi bi-clock-history" aria-hidden="true"></i>
          <span>Editado {{ $doc->updated_at->locale('es')->diffForHumans() }}@if ($supervise && $doc->editor) · {{ $doc->editor->name }}@endif</span>
        </li>
      </ul>
    </div>

    <a class="hp-btn-outline" href="{{ route('documents.edit', $doc) }}">
      <i class="bi {{ $canEdit || $canReview ? 'bi-pencil-square' : 'bi-eye' }} mr-2" aria-hidden="true"></i>{{ $openLabel }}
    </a>

    <div class="hp-card-foot">
      {{-- Descarga: solo documentos completados (el backend también lo exige). --}}
      @can('download', $doc)
        <a href="{{ route('documents.download', $doc) }}" class="hp-btn-sm">
          <i class="bi bi-download" aria-hidden="true"></i> Descargar
        </a>
      @else
        <button type="button" class="hp-btn-sm" disabled title="Disponible cuando el documento esté completado">
          <i class="bi bi-download" aria-hidden="true"></i> Descargar
        </button>
      @endcan

      @can('changeStatus', $doc)
        <form method="POST" action="{{ route('documents.status', $doc) }}"
              data-confirm="{{ $done ? 'El documento volverá a borrador: no se podrá descargar y su autor podrá editarlo de nuevo.' : 'Se podrá descargar y el autor ya no podrá editarlo. Puedes reabrirlo cuando quieras.' }}"
              data-confirm-title="{{ $done ? 'Volver a borrador' : 'Marcar como completado' }}"
              data-confirm-text="{{ $done ? 'Volver a borrador' : 'Completar' }}"
              data-confirm-variant="{{ $done ? 'warning' : 'primary' }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ $next }}">
          <button type="submit" class="hp-btn-sm {{ $done ? '' : 'hp-btn-sm--ok' }}">
            <i class="bi {{ $done ? 'bi-arrow-counterclockwise' : 'bi-check2-circle' }}" aria-hidden="true"></i> {{ $done ? 'Volver a borrador' : 'Marcar completado' }}
          </button>
        </form>
      @endcan

      {{-- Eliminar: solo borradores. --}}
      @can('delete', $doc)
        <form method="POST" action="{{ route('documents.destroy', $doc) }}"
              data-confirm="Se eliminará «{{ $doc->title }}» ({{ $doc->code }}) y su archivo. Esta acción no se puede deshacer."
              data-confirm-title="Eliminar documento" data-confirm-text="Eliminar" data-confirm-variant="danger">
          @csrf
          @method('DELETE')
          <button type="submit" class="hp-btn-sm hp-btn-sm--danger">
            <i class="bi bi-trash3" aria-hidden="true"></i> Eliminar
          </button>
        </form>
      @endcan
    </div>
  </div>
</article>
