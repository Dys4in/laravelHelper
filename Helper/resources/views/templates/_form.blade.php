{{--
  Campos de plantilla. Se usa en la ventana «Subir plantilla» ($modalId) y en la página de edición.
  Variables: $template · $areas (solo el admin elige área) · $modalId (opcional)
--}}
@php
  $isEdit = $template->exists;
  $modalId = $modalId ?? null;
  $p = $modalId ? $modalId.'-' : '';
  // Dentro de una ventana, los valores y errores previos solo se muestran en la que se envió.
  $mine = $modalId ? old('_modal') === $modalId : true;
  $val = fn (string $key, $default = null) => $mine ? old($key, $default) : $default;
  $err = fn (string $key) => $mine ? $errors->first($key) : '';
  $me = auth()->user();
@endphp

<div class="hp-modal-grid">
  <div class="sm:col-span-2">
    <label for="{{ $p }}name" class="hp-label">Nombre de la plantilla *</label>
    <input id="{{ $p }}name" name="name" type="text" maxlength="150" value="{{ $val('name', $template->name) }}" required
           class="hp-input" @if ($err('name')) aria-invalid="true" @endif>
    @if ($e = $err('name'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div class="sm:col-span-2">
    <label for="{{ $p }}description" class="hp-label">Descripción</label>
    <input id="{{ $p }}description" name="description" type="text" maxlength="255" value="{{ $val('description', $template->description) }}"
           placeholder="Para qué se usa esta plantilla" class="hp-input" @if ($err('description')) aria-invalid="true" @endif>
    @if ($e = $err('description'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  @if ($me->isAdmin())
    <div>
      <label for="{{ $p }}area_id" class="hp-label">Asignar al área *</label>
      <select id="{{ $p }}area_id" name="area_id" required class="hp-select" @if ($err('area_id')) aria-invalid="true" @endif>
        <option value="">Selecciona un área</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected((string) $val('area_id', $template->area_id) === (string) $area->id)>
            {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
          </option>
        @endforeach
      </select>
      @if ($e = $err('area_id'))<p class="hp-error">{{ $e }}</p>@endif
    </div>
  @else
    <div>
      <span class="hp-label">Asignada al área</span>
      <p class="hp-static"><span class="font-semibold">{{ $me->area?->name }}</span></p>
    </div>
  @endif

  <div>
    <label for="{{ $p }}status" class="hp-label">Estado</label>
    <select id="{{ $p }}status" name="status" class="hp-select">
      <option value="active" @selected($val('status', $template->status) === 'active')>Activa</option>
      <option value="inactive" @selected($val('status', $template->status) === 'inactive')>Inactiva</option>
    </select>
  </div>

  <div class="sm:col-span-2">
    <label for="{{ $p }}file" class="hp-label">{{ $isEdit ? 'Reemplazar archivo (.docx)' : 'Archivo .docx *' }}</label>
    <input id="{{ $p }}file" name="file" type="file" accept=".docx" {{ $isEdit ? '' : 'required' }}
           class="hp-file" @if ($err('file')) aria-invalid="true" @endif>
    @if ($e = $err('file'))<p class="hp-error">{{ $e }}</p>@endif
    <p class="hp-help">
      Máximo {{ round(config('onlyoffice.max_upload_kb') / 1024) }} MB.
      @if ($isEdit)
        Archivo actual: <span class="font-medium">{{ $template->original_name }}</span>. Déjalo vacío para conservarlo; los documentos ya creados no cambian.
      @else
        Los usuarios del área asignada podrán crear documentos a partir de ella.
      @endif
    </p>
  </div>
</div>
