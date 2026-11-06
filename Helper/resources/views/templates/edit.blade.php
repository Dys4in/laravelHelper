<x-panel-layout title="Editar plantilla">
  <form method="POST" action="{{ route('templates.update', $template) }}" enctype="multipart/form-data" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @method('PUT')
    @include('templates._form', ['areas' => $areas])

    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
      <a href="{{ route('templates.index') }}" class="hp-btn-outline">Cancelar</a>
      <button type="submit" class="hp-btn-outline">Guardar cambios</button>
    </div>
  </form>
</x-panel-layout>
