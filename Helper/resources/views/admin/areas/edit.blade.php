<x-admin-layout title="Editar área">
  <form method="POST" action="{{ route('admin.areas.update', $area) }}" class="max-w-xl space-y-5 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @method('PUT')

    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Nombre del área *</label>
      <input id="name" name="name" type="text" value="{{ old('name', $area->name) }}" required maxlength="100"
             class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('name') !border-red-400 @enderror">
    </div>

    <div>
      <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
      <input id="description" name="description" type="text" value="{{ old('description', $area->description) }}" maxlength="255"
             class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('description') !border-red-400 @enderror">
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
      <a href="{{ route('admin.areas.index') }}" class="hp-btn-outline">Cancelar</a>
      <button type="submit" class="hp-btn-outline">Guardar cambios</button>
    </div>
  </form>
</x-admin-layout>
