<x-admin-layout title="Editar usuario">
  <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @method('PUT')
    @include('admin.users._form')

    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
      <a href="{{ route('admin.users.index') }}" class="hp-btn-outline">Cancelar</a>
      <button type="submit" class="hp-btn-outline">Guardar cambios</button>
    </div>
  </form>
</x-admin-layout>
