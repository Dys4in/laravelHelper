@php
  $me = auth()->user();
  $isAdmin = $me->isAdmin();
  $canUpload = $me->can('create', \App\Models\Template::class);
  $reopenName = $templates->getCollection()->firstWhere('id', (int) old('template_id'))?->name;
@endphp

<x-panel-layout title="Plantillas">
  <section class="hp-overview">
    <div>
      <p class="hp-overview-sub">
        @if ($isAdmin)
          Plantillas de todas las áreas. Úsalas para crear documentos o adminístralas.
        @elseif ($canManage)
          Plantillas del área {{ $me->area?->name }}. Sube las que tu equipo necesita y actívalas o desactívalas cuando quieras.
        @else
          Elige una plantilla de tu área para crear un documento. Se crea una copia: la plantilla original no cambia.
        @endif
      </p>
    </div>

    @if ($canManage)
      <div class="hp-stats">
        <a class="hp-stat" href="{{ route('templates.index', array_filter(['q' => request('q'), 'area' => request('area'), 'status' => request('status') === 'active' ? null : 'active'])) }}" @if (request('status') === 'active') aria-current="true" @endif>
          <span class="hp-stat-label">Activas:</span><span class="hp-stat-value">{{ $counts['active'] }}</span>
        </a>
        <a class="hp-stat" href="{{ route('templates.index', array_filter(['q' => request('q'), 'area' => request('area'), 'status' => request('status') === 'inactive' ? null : 'inactive'])) }}" @if (request('status') === 'inactive') aria-current="true" @endif>
          <span class="hp-stat-label">Inactivas:</span><span class="hp-stat-value">{{ $counts['inactive'] }}</span>
        </a>
      </div>
    @endif
  </section>

  {{-- Buscador y, al otro lado, "Subir plantilla" --}}
  <section class="flex flex-wrap items-start justify-between gap-3">
    <form method="GET" action="{{ route('templates.index') }}" data-autosubmit class="flex flex-1 flex-wrap items-center gap-3">
      <div class="hp-search hp-search--block">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar plantilla..." aria-label="Buscar plantillas" autocomplete="off">
      </div>
    </form>

    @if ($canUpload)
      <button type="button" class="hp-btn-outline" data-modal-open="upload-template">
        <i class="bi bi-upload mr-2" aria-hidden="true"></i><span>Subir plantilla</span>
      </button>
    @endif
  </section>

  <section class="hp-docs-grid" aria-label="Lista de plantillas">
    @forelse ($templates as $t)
      @php $manage = $me->can('manage', $t); @endphp

      <article class="hp-card">
        <div class="hp-card-body">
          <div class="hp-card-row">
            @if ($canManage)
              <span class="hp-badge {{ $t->isActive() ? 'hp-badge--completed' : 'hp-badge--draft' }}">{{ $t->isActive() ? 'activa' : 'inactiva' }}</span>
            @else
              <span class="hp-badge hp-badge--draft">{{ $t->area?->name ?? 'general' }}</span>
            @endif
            <i class="bi bi-file-earmark-word text-xl text-brand" aria-hidden="true"></i>
          </div>

          <div class="hp-card-details">
            <p class="hp-card-code">{{ $t->name }}</p>
            @if ($t->description)
              <p class="line-clamp-2 text-sm text-gray-600">{{ $t->description }}</p>
            @endif
            @if ($canManage)
              <p class="truncate text-xs text-gray-500"><i class="bi bi-diagram-3" aria-hidden="true"></i> {{ $t->area?->name ?? 'Todas las áreas' }}</p>
            @endif
            <p class="hp-card-date">Subida el {{ $t->created_at->format('d/m/Y') }}@if ($t->creator) por {{ $t->creator->name }}@endif</p>
            @if ($canManage)
              <p class="hp-card-date">{{ $t->documents_count }} {{ $t->documents_count === 1 ? 'documento creado' : 'documentos creados' }}</p>
            @endif
          </div>

          @if ($t->isActive())
            <button type="button" class="hp-btn-outline w-full cursor-pointer" data-modal-open="use-template"
                    data-fill-template_id="{{ $t->id }}" data-fill-template_name="{{ $t->name }}" data-fill-area_id="{{ $t->area_id }}">
              Usar plantilla
            </button>
          @else
            <span class="hp-btn-outline w-full cursor-not-allowed opacity-50" aria-disabled="true">Plantilla inactiva</span>
          @endif

          @if ($manage)
            <div class="hp-card-foot">
              <a href="{{ route('templates.download', $t) }}" class="hp-btn-sm"><i class="bi bi-download" aria-hidden="true"></i> Descargar</a>
              <a href="{{ route('templates.edit', $t) }}" class="hp-btn-sm"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>

              <form method="POST" action="{{ route('templates.status', $t) }}"
                    data-confirm="{{ $t->isActive() ? 'Los usuarios dejarán de ver «'.$t->name.'» al crear documentos. Los documentos ya creados no se afectan.' : 'Los usuarios podrán volver a elegir «'.$t->name.'».' }}"
                    data-confirm-title="{{ $t->isActive() ? 'Desactivar plantilla' : 'Activar plantilla' }}"
                    data-confirm-text="{{ $t->isActive() ? 'Desactivar' : 'Activar' }}"
                    data-confirm-variant="{{ $t->isActive() ? 'warning' : 'primary' }}">
                @csrf @method('PATCH')
                <button type="submit" class="hp-btn-sm {{ $t->isActive() ? 'hp-btn-sm--warn' : 'hp-btn-sm--ok' }}">
                  <i class="bi {{ $t->isActive() ? 'bi-pause-circle' : 'bi-play-circle' }}" aria-hidden="true"></i> {{ $t->isActive() ? 'Desactivar' : 'Activar' }}
                </button>
              </form>

              <form method="POST" action="{{ route('templates.destroy', $t) }}"
                    data-confirm="«{{ $t->name }}» dejará de aparecer en la lista. Los documentos creados con ella se conservan."
                    data-confirm-title="Eliminar plantilla" data-confirm-text="Eliminar" data-confirm-variant="danger">
                @csrf @method('DELETE')
                <button type="submit" class="hp-btn-sm hp-btn-sm--danger">
                  <i class="bi bi-trash3" aria-hidden="true"></i> Eliminar
                </button>
              </form>
            </div>
          @endif
        </div>
      </article>
    @empty
      <div class="hp-empty md:col-span-2 xl:col-span-3">
        @if (request()->hasAny(['q', 'area', 'status']))
          No hay plantillas que coincidan con tu búsqueda.
        @elseif ($canUpload)
          Aún no hay plantillas. Sube la primera con “Subir plantilla”.
        @else
          Todavía no hay plantillas para tu área. Pídele al moderador de tu área que suba una.
        @endif
      </div>
    @endforelse
  </section>

  {{ $templates->links() }}

  {{-- ================= Ventana: Usar plantilla ================= --}}
  <x-app-modal id="use-template" title="Usar plantilla" description="Se crea una copia para que la edites; la plantilla original no cambia." size="md">
    <form method="POST" action="{{ route('documents.store') }}" class="space-y-5">
      @csrf
      <input type="hidden" name="_modal" value="use-template">
      <input type="hidden" name="template_id" value="{{ old('template_id') }}" data-fill="template_id">

      <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm">
        <span class="text-gray-500">Plantilla:</span>
        <span class="font-semibold text-gray-900" data-fill="template_name">{{ $reopenName }}</span>
      </div>

      <div>
        <label for="use_title" class="block text-sm font-medium text-gray-700">Título del documento *</label>
        <input id="use_title" name="title" type="text" maxlength="150" required value="{{ old('title') }}"
               placeholder="Ej. Informe de verificación 03"
               class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('title') !border-red-400 @enderror">
      </div>

      @if ($isAdmin)
        <div>
          <label for="use_area" class="block text-sm font-medium text-gray-700">Área del documento *</label>
          <select id="use_area" name="area_id" required data-fill="area_id"
                  class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('area_id') !border-red-400 @enderror">
            <option value="">Selecciona un área</option>
            @foreach ($formAreas as $area)
              <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->name }}</option>
            @endforeach
          </select>
        </div>
      @else
        <p class="text-sm text-gray-600">Se creará en tu área: <span class="font-semibold text-gray-900">{{ $me->area?->name }}</span></p>
      @endif

      <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
        <button type="button" data-modal-close class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
        <button type="submit" class="hp-btn-primary"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Crear y abrir editor</span></button>
      </div>
    </form>
  </x-app-modal>

  {{-- ================= Ventana: Subir plantilla ================= --}}
  @if ($canUpload)
    <x-app-modal id="upload-template" title="Subir plantilla" description="Archivo .docx que tu equipo podrá usar para crear documentos." size="lg">
      <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <input type="hidden" name="_modal" value="upload-template">
        @include('templates._form', ['template' => $newTemplate, 'areas' => $formAreas])

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
          <button type="button" data-modal-close class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
          <button type="submit" class="hp-btn-outline"><i class="bi bi-upload mr-2" aria-hidden="true"></i><span>Subir plantilla</span></button>
        </div>
      </form>
    </x-app-modal>
  @endif
</x-panel-layout>
