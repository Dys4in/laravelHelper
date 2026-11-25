<x-panel-layout title="Editar plantilla">
  <form method="POST" action="{{ route('templates.update', $template) }}" enctype="multipart/form-data" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @method('PUT')
    @include('templates._form', ['areas' => $areas])

    <div class="hp-dialog-foot">
      <a href="{{ route('templates.index') }}" class="hp-btn-confirm hp-btn-confirm--outline">Cancelar</a>
      <button type="submit" class="hp-btn-confirm hp-btn-confirm--primary">Guardar cambios</button>
    </div>
  </form>
</x-panel-layout>
