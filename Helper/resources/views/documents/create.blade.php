<x-panel-layout title="Nuevo documento">
  <section>
    <h2 class="mb-1 text-xl font-bold text-gray-900">Nuevo documento</h2>
    <p class="text-sm text-gray-600">Elige una plantilla y ponle un título. Se crea una copia que puedes editar libremente; la plantilla original no cambia.</p>
  </section>

  @if ($templates->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
      <p class="font-semibold text-gray-900">No hay plantillas disponibles</p>
      <p class="mt-1 text-sm text-gray-600">
        @if (auth()->user()->hasRole('admin', 'mod'))
          Carga una plantilla .docx para que los usuarios puedan crear documentos.
        @else
          Pide al moderador de tu área que cargue una plantilla.
        @endif
      </p>
      @if (auth()->user()->hasRole('admin', 'mod'))
        <a href="{{ route('templates.create') }}" class="hp-btn-primary mt-5 inline-flex"><i class="bi bi-upload" aria-hidden="true"></i><span>Cargar plantilla</span></a>
      @endif
    </div>
  @else
    <form method="POST" action="{{ route('documents.store') }}" class="space-y-6">
      @csrf

      <fieldset>
        <legend class="mb-3 text-sm font-semibold text-gray-900">1. Plantilla</legend>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          @foreach ($templates as $t)
            <label class="block cursor-pointer">
              <input type="radio" name="template_id" value="{{ $t->id }}" class="peer sr-only"
                     @checked((string) old('template_id') === (string) $t->id) required>
              <div class="h-full rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 peer-checked:border-brand peer-checked:ring-2 peer-checked:ring-brand peer-focus-visible:ring-2 peer-focus-visible:ring-gray-400">
                <div class="flex items-start gap-3">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                    <i class="bi bi-file-earmark-word text-lg" aria-hidden="true"></i>
                  </span>
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $t->name }}</p>
                    @if ($t->description)
                      <p class="mt-0.5 text-sm text-gray-600">{{ $t->description }}</p>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">{{ $t->area?->name ?? 'Todas las áreas' }}</p>
                  </div>
                </div>
              </div>
            </label>
          @endforeach
        </div>
      </fieldset>

      <fieldset class="max-w-xl space-y-4">
        <legend class="mb-3 text-sm font-semibold text-gray-900">2. Datos del documento</legend>

        <div>
          <label for="title" class="block text-sm font-medium text-gray-700">Título *</label>
          <input id="title" name="title" type="text" maxlength="150" required value="{{ old('title') }}"
                 placeholder="Ej. Informe de verificación 03"
                 class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('title') !border-red-400 @enderror">
        </div>

        @if (auth()->user()->isAdmin())
          <div>
            <label for="area_id" class="block text-sm font-medium text-gray-700">Área del documento *</label>
            <select id="area_id" name="area_id" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('area_id') !border-red-400 @enderror">
              <option value="">Selecciona un área</option>
              @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->name }}</option>
              @endforeach
            </select>
          </div>
        @else
          <p class="text-sm text-gray-600">Se creará en tu área: <span class="font-semibold text-gray-900">{{ auth()->user()->area?->name }}</span></p>
        @endif
      </fieldset>

      <div class="flex items-center gap-3">
        <button type="submit" class="hp-btn-primary"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Crear y abrir editor</span></button>
        <a href="{{ route('documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
      </div>
    </form>
  @endif
</x-panel-layout>
