{{-- Ventana única para renombrar documentos; el icono de cada tarjeta rellena document_id y title. --}}
<x-app-modal id="rename-document" title="Cambiar nombre del documento" description="El nombre aparece en la lista, los registros y el editor." size="md">
  <form method="POST" action="{{ route('documents.rename') }}" class="space-y-5">
    @csrf
    @method('PATCH')
    <input type="hidden" name="_modal" value="rename-document">
    <input type="hidden" name="document_id" value="{{ old('document_id') }}" data-fill="document_id">
    <div>
      <label for="rename_title" class="hp-label">Nombre del documento *</label>
      <input id="rename_title" name="title" type="text" maxlength="150" required value="{{ old('title') }}" data-fill="title"
             class="hp-input" @if ($errors->first('title')) aria-invalid="true" @endif>
      @if ($e = $errors->first('title'))<p class="hp-error">{{ $e }}</p>@endif
    </div>
    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
      <button type="button" class="hp-btn-outline" data-modal-close>Cancelar</button>
      <button type="submit" class="hp-btn-outline"><i class="bi bi-check2 mr-2" aria-hidden="true"></i>Guardar nombre</button>
    </div>
  </form>
</x-app-modal>
