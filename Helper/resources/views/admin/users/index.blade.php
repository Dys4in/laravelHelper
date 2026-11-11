<x-admin-layout title="Usuarios">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <p class="text-sm text-gray-600">Solo el administrador puede crear cuentas y asignar su rol y área.</p>
    </div>
      <button type="button" data-modal-open="create-user" class="hp-btn-outline">
          <i class="bi bi-file-earmark-plus mr-2" aria-hidden="true"></i>
        <span>Nuevo usuario</span>
      </button>
  </section>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="hp-search" role="search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, abreviatura, correo o usuario" class="h-11 px-4 rounded-xl max-w-full"
             aria-label="Buscar usuarios" autocomplete="off">
    </div>

    <select name="area" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todas las áreas</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected(request('area') == $area->id)>{{ $area->name }}{{ $area->trashed() ? ' (eliminada)' : '' }}</option>
      @endforeach
    </select>

    <select name="role" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los roles</option>
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>

    <div class="flex gap-2">
      <a href="{{ route('admin.users.index') }}" class="hp-btn-outline flex-1">Limpiar</a>
    </div>
  </form>

  {{-- Tabla --}}
  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Usuario</th>
          <th class="px-4 py-3">Rol</th>
          <th class="px-4 py-3">Área</th>
          <th class="px-4 py-3">Estado</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($users as $u)
          <tr>
            <td class="px-4 py-3">
              <p class="font-semibold text-gray-900">{{ $u->name }}</p>
              <p class="text-xs text-gray-500">{{ $u->short_name }} · {{ $u->email }}</p>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{{ $u->role->label() }}</span>
            </td>
            <td class="px-4 py-3 text-gray-700">
              @if ($u->area)
                {{ $u->area->name }}
                @if ($u->area->trashed())
                  <span class="text-xs text-red-600">(eliminada)</span>
                @elseif (! $u->area->isActive())
                  <span class="text-xs text-amber-600">(suspendida)</span>
                @endif
              @else
                <span class="text-gray-400">—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              @if ($u->isActive())
                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Activo</span>
              @else
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Inactivo</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.users.edit', $u) }}" class="hp-btn-outline px-3 py-1.5 text-xs font-medium ">Editar</a>

                @unless ($u->is(auth()->user()))
                  <form method="POST" action="{{ route('admin.users.status', $u) }}"
                        data-confirm="{{ $u->isActive() ? $u->name.' no podrá iniciar sesión hasta que lo reactives.' : $u->name.' volverá a poder iniciar sesión.' }}"
                        data-confirm-title="{{ $u->isActive() ? 'Desactivar usuario' : 'Reactivar usuario' }}"
                        data-confirm-text="{{ $u->isActive() ? 'Desactivar' : 'Reactivar' }}"
                        data-confirm-variant="{{ $u->isActive() ? 'warning' : 'primary' }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $u->isActive() ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                      {{ $u->isActive() ? 'Desactivar' : 'Activar' }}
                    </button>
                  </form>
                @endunless
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No se encontraron usuarios.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $users->links() }}

  {{-- Ventana: Nuevo usuario --}}
  <x-app-modal id="create-user" title="Nuevo usuario" description="Solo el administrador crea cuentas. Elige su rol y su área." size="xl">
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6" autocomplete="off">
      @csrf
      <input type="hidden" name="_modal" value="create-user">
      @include('admin.users._form', ['user' => $newUser, 'areas' => $formAreas, 'roles' => $roles])

      <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
        <button type="button" data-modal-close class="hp-btn-outline">Cancelar</button>
        <button type="submit" class="hp-btn-outline">Crear usuario</button>
      </div>
    </form>
  </x-app-modal>
</x-admin-layout>
