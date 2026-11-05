{{--
  Tarjeta de plantilla: mismo diseño que la de documentos.
  Variables: $t (plantilla) · $canManage (moderador/admin: muestra estado y acciones de administración)
--}}
@php
  $canUse = auth()->user()->can('useTemplate', $t);
  $manage = $canManage && auth()->user()->can('manage', $t);
  $fill = [
      'values' => ['template_id' => $t->id, 'area_id' => $t->area_id ?? ''],
      'text' => ['template-name' => $t->name, 'template-desc' => $t->description ?: 'Sin descripción'],
  ];
@endphp

<article class="hp-card" data-template>
  <div class="hp-card-body">
    <div class="hp-card-row">
      @if ($canManage)
        <span class="hp-badge {{ $t->isActive() ? 'hp-badge--active' : 'hp-badge--inactive' }}">{{ $t->isActive() ? 'Activa' : 'Inactiva' }}</span>
      @else
        <span class="hp-badge hp-badge--tpl">Plantilla</span>
      @endif
      <span class="hp-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-word"></i></span>
    </div>

    <div class="hp-card-details">
      <p class="hp-card-code" title="{{ $t->name }}">{{ $t->name }}</p>
      <p class="hp-card-text">{{ $t->description ?: 'Sin descripción' }}</p>

      <ul class="hp-card-meta">
        <li>
          <i class="bi bi-calendar-plus" aria-hidden="true"></i>
          <span>Creada <time datetime="{{ $t->created_at->toIso8601String() }}" title="{{ $t->created_at->format('d/m/Y H:i') }}">{{ $t->created_at->format('d/m/Y') }}</time></span>
        </li>
        <li>
          <i class="bi bi-building" aria-hidden="true"></i>
          <span class="truncate">
            {{ $t->area?->name ?? 'Sin área asignada' }}@if ($t->area?->trashed()) (eliminada)@endif
          </span>
        </li>
        @if ($t->creator)
          <li><i class="bi bi-person" aria-hidden="true"></i><span class="truncate">Subida por {{ $t->creator->name }}</span></li>
        @endif
        @if ($canManage)
          <li>
            <i class="bi bi-files" aria-hidden="true"></i>
            <span>{{ $t->documents_count }} {{ Str::plural('uso', $t->documents_count) }} · {{ \Illuminate\Support\Number::fileSize((int) $t->file_size, precision: 1) }}</span>
          </li>
        @endif
      </ul>
    </div>

    @if ($canUse)
      <button type="button" class="hp-btn-outline" data-modal-open="template-use" data-fill='@json($fill)'>
        <i class="bi bi-file-earmark-plus mr-2" aria-hidden="true"></i>Usar plantilla
      </button>
    @elseif ($canManage)
      <button type="button" class="hp-btn-outline" disabled title="Activa la plantilla para poder usarla">
        <i class="bi bi-file-earmark-plus mr-2" aria-hidden="true"></i>Usar plantilla
      </button>
    @endif

    @if ($manage)
      <div class="hp-card-foot">
        <a href="{{ route('templates.download', $t) }}" class="hp-btn-outline"><i class="bi bi-download" aria-hidden="true"></i> Descargar</a>
        <a href="{{ route('templates.edit', $t) }}" class="hp-btn-outline"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>

        <form method="POST" action="{{ route('templates.status', $t) }}"
              data-confirm="{{ $t->isActive() ? 'Los usuarios dejarán de ver «'.$t->name.'» al crear documentos. Los documentos ya creados no se afectan.' : 'Los usuarios del área podrán volver a elegir «'.$t->name.'».' }}"
              data-confirm-title="{{ $t->isActive() ? 'Desactivar plantilla' : 'Activar plantilla' }}"
              data-confirm-text="{{ $t->isActive() ? 'Desactivar' : 'Activar' }}"
              data-confirm-variant="{{ $t->isActive() ? 'warning' : 'primary' }}">
          @csrf
          @method('PATCH')
          <button type="submit" class="hp-btn-sm {{ $t->isActive() ? 'hp-btn-sm--warn' : 'hp-btn-sm--ok' }}">
            <i class="bi {{ $t->isActive() ? 'bi-pause-circle' : 'bi-play-circle' }}" aria-hidden="true"></i> {{ $t->isActive() ? 'Desactivar' : 'Activar' }}
          </button>
        </form>

        <form method="POST" action="{{ route('templates.destroy', $t) }}"
              data-confirm="«{{ $t->name }}» dejará de aparecer en la lista. Los documentos creados con ella se conservan."
              data-confirm-title="Eliminar plantilla" data-confirm-text="Eliminar" data-confirm-variant="danger">
          @csrf
          @method('DELETE')
          <button type="submit" class="hp-btn-sm hp-btn-sm--danger" aria-label="Eliminar plantilla {{ $t->name }}">
            <i class="bi bi-trash3" aria-hidden="true"></i> Eliminar
          </button>
        </form>
      </div>
    @endif
  </div>
</article>
