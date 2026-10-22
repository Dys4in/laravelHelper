<x-admin-layout title="Nuevo usuario">
  <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-3xl space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @include('admin.users._form')

    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
      <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
      <button type="submit" class="hp-btn-primary">Crear usuario</button>
    </div>
  </form>
</x-admin-layout>
