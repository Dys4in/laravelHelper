<x-admin-layout title="Usuarios">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <p class="text-sm text-gray-600">Los coordinadores ven, crean, editan y desactivan usuarios de sus áreas; los administradores y jefes de área gestionan todas las cuentas.</p>
    </div>
      <button type="button" data-modal-open="create-user" class="hp-btn-outline">
          <i class="bi bi-file-earmark-plus mr-2" aria-hidden="true"></i>
        <span>Nuevo usuario</span>
      </button>
  </section>

  @if (session('temporary_password'))
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
      <strong>Contraseña temporal de {{ session('new_user_email') }}:</strong>
      <code class="break-all select-all">{{ session('temporary_password') }}</code>
      <p class="mt-1">Cópiala ahora y entrégala por un canal seguro. No volverá a mostrarse. El usuario deberá cambiarla al ingresar.</p>
    </div>
  @endif
  @if (session('recovery_link'))
    <div class="rounded-xl border border-indigo-300 bg-indigo-50 p-4 text-sm text-indigo-900">
      <strong>Enlace de recuperación generado (2 visitas, 7 días):</strong>
      <p class="mt-1 break-all select-all">{{ session('recovery_link') }}</p>
      <p class="mt-1">Cópialo y compártelo de forma privada. Solo el primer envío cambia la contraseña. El enlace anterior del usuario queda invalidado.</p>
    </div>
  @endif

  {{-- Filtros --}}
  <form method="GET" action="{{ route('admin.users.index') }}" class="hp-filterbar">
    <div class="hp-search" role="search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, abreviatura, correo o usuario"
             aria-label="Buscar usuarios" autocomplete="off">
    </div>

    <select name="area" onchange="this.form.submit()" class="hp-select">
      <option value="">Todas las áreas</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected(request('area') == $area->id)>{{ $area->name }}{{ $area->trashed() ? ' (eliminada)' : '' }}</option>
      @endforeach
    </select>

    <select name="role" onchange="this.form.submit()" class="hp-select">
      <option value="">Todos los roles</option>
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>

    <div class="hp-filter-clear">
      <a href="{{ route('admin.users.index') }}" class="hp-btn-outline">Limpiar</a>
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
              @if ($u->hasRole('mod') && $u->areas->isNotEmpty())
                {{ $u->areas->pluck('name')->implode(', ') }}
              @elseif ($u->area)
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
                @php $editable = $canEditUser($u); @endphp
                @if (! $editable)
                  <span class="text-xs text-gray-400">{{ $u->hasRole(\App\Enums\UserRole::Admin) || $canToggleStatus ? 'Cuenta protegida' : 'Solo lectura' }}</span>
                @else
                  <button type="button" class="hp-btn-outline px-3 py-1.5 text-xs font-medium" data-modal-open="edit-user-{{ $u->id }}">Editar</button>

                  @if (! $u->is(auth()->user()))
                    <form method="POST" action="{{ route('admin.users.recovery-link', $u) }}" data-confirm="El enlace anterior de recuperación quedará invalidado." data-confirm-title="Recuperar contraseña">
                      @csrf
                      <button type="submit" class="hp-btn-outline px-3 py-1.5 text-xs font-medium">Recuperar contraseña</button>
                    </form>
                  @endif
                  @if ($canToggleStatus && ! $u->is(auth()->user()))
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
                  @endif
                @endif
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
  <x-app-modal id="create-user" title="Nuevo usuario" description="Elige los datos del usuario y el área a la que pertenecerá." size="xl">
    <form id="create-user-form" method="POST" action="{{ route('admin.users.store') }}" class="space-y-6" autocomplete="off">
      @csrf
      <input type="hidden" name="_modal" value="create-user">
      @include('admin.users._form', ['user' => $newUser, 'areas' => $formAreas, 'roles' => $formRoles, 'modalId' => 'create-user'])

      <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
        <button type="button" data-modal-close class="hp-btn-outline">Cancelar</button>
        <button type="submit" class="hp-btn-outline">Crear usuario</button>
      </div>
    </form>
  </x-app-modal>

  <script>
    (function () {
      var form = document.getElementById('create-user-form');
      if (!form) return;
      var role = form.querySelector('[data-role-select]');
      if (!role) return;

      function syncAdminConfirm() {
        if (role.value === 'admin' || role.value === 'jefe_de_area') {
          var roleLabel = role.options[role.selectedIndex].text;
          form.setAttribute('data-confirm', 'Este usuario tendrá acceso total al sistema, incluida la creación de otros administradores.');
          form.setAttribute('data-confirm-title', 'Crear ' + roleLabel);
          form.setAttribute('data-confirm-text', 'Crear ' + roleLabel);
          form.setAttribute('data-confirm-variant', 'warning');
        } else {
          ['data-confirm', 'data-confirm-title', 'data-confirm-text', 'data-confirm-variant'].forEach(function (attr) {
            form.removeAttribute(attr);
          });
        }
      }

      role.addEventListener('change', syncAdminConfirm);
      syncAdminConfirm();
    })();
  </script>

  {{-- Ventanas: Editar usuario --}}
  @foreach ($users as $u)
    @continue(! $canEditUser($u))
    @php
      $editModal = 'edit-user-'.$u->id;
      $userAreas = $formAreas->concat($u->areas)->concat($u->area ? [$u->area] : [])->unique('id')->sortBy('name')->values();
    @endphp
    <x-app-modal :id="$editModal" title="Editar usuario" description="Actualiza los datos, el rol o el área de {{ $u->name }}." size="xl">
      <form method="POST" action="{{ route('admin.users.update', $u) }}" class="space-y-6" autocomplete="off">
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="{{ $editModal }}">
        @include('admin.users._form', ['user' => $u, 'areas' => $userAreas, 'roles' => $formRoles, 'modalId' => $editModal])

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
          <button type="button" data-modal-close class="hp-btn-outline">Cancelar</button>
          <button type="submit" class="hp-btn-outline">Guardar cambios</button>
        </div>
      </form>
    </x-app-modal>
  @endforeach
</x-admin-layout>
