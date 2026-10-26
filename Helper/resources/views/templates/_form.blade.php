@php
  $isEdit = $template->exists;
  $field = 'mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand';
  $label = 'block text-sm font-medium text-gray-700';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
  <div class="sm:col-span-2">
    <label for="name" class="{{ $label }}">Nombre de la plantilla *</label>
    <input id="name" name="name" type="text" maxlength="150" value="{{ old('name', $template->name) }}" required
           class="{{ $field }} @error('name') !border-red-400 @enderror">
  </div>

  <div class="sm:col-span-2">
    <label for="description" class="{{ $label }}">Descripción</label>
    <input id="description" name="description" type="text" maxlength="255" value="{{ old('description', $template->description) }}"
           placeholder="Para qué se usa esta plantilla"
           class="{{ $field }} @error('description') !border-red-400 @enderror">
  </div>

  @if (auth()->user()->isAdmin())
    <div>
      <label for="area_id" class="{{ $label }}">Disponible para</label>
      <select id="area_id" name="area_id" class="{{ $field }} @error('area_id') !border-red-400 @enderror">
        <option value="">Todas las áreas</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected((string) old('area_id', $template->area_id) === (string) $area->id)>
            {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
          </option>
        @endforeach
      </select>
    </div>
  @else
    <div>
      <span class="{{ $label }}">Disponible para</span>
      <p class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
        Tu área: <span class="font-semibold">{{ auth()->user()->area?->name }}</span>
      </p>
    </div>
  @endif

  <div>
    <label for="status" class="{{ $label }}">Estado</label>
    <select id="status" name="status" class="{{ $field }}">
      <option value="active" @selected(old('status', $template->status) === 'active')>Activa</option>
      <option value="inactive" @selected(old('status', $template->status) === 'inactive')>Inactiva</option>
    </select>
  </div>

  <div class="sm:col-span-2">
    <label for="file" class="{{ $label }}">{{ $isEdit ? 'Reemplazar archivo (.docx)' : 'Archivo .docx *' }}</label>
    <input id="file" name="file" type="file" accept=".docx" {{ $isEdit ? '' : 'required' }}
           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-gray-900 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-800 @error('file') !border-red-400 @enderror">
    <p class="mt-1 text-xs text-gray-500">
      Máximo {{ round(config('onlyoffice.max_upload_kb') / 1024) }} MB.
      @if ($isEdit)
        Archivo actual: <span class="font-medium">{{ $template->original_name }}</span>. Déjalo vacío para conservarlo; los documentos ya creados no cambian.
      @endif
    </p>
  </div>
</div>
