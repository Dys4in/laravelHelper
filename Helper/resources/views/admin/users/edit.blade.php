<x-admin-layout title="Editar usuario">
  <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @method('PUT')
    @include('admin.users._form')

    <div class="hp-dialog-foot">
      <a href="{{ route('admin.users.index') }}" class="hp-btn-confirm hp-btn-confirm--outline">Cancelar</a>
      <button type="submit" class="hp-btn-confirm hp-btn-confirm--primary">Guardar cambios</button>
    </div>
  </form>
</x-admin-layout>
