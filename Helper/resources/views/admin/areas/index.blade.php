<x-admin-layout title="Áreas">
  <section>
    <h2 class="mb-1 text-xl font-bold text-gray-900">Áreas</h2>
    <p class="text-sm text-gray-600">Cada usuario pertenece a un área. Suspender un área impide que sus usuarios inicien sesión.</p>
  </section>

  {{-- Crear --}}
  <form method="POST" action="{{ route('admin.areas.store') }}" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-[1fr_1.5fr_auto] md:items-end">
    @csrf
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Nombre del área *</label>
      <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100"
             class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('name') !border-red-400 @enderror">
    </div>
    <div>
      <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
      <input id="description" name="description" type="text" value="{{ old('description') }}" maxlength="255"
             class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('description') !border-red-400 @enderror">
    </div>
    <button type="submit" class="hp-btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Crear área</span></button>
  </form>

  {{-- Listado --}}
  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Área</th>
          <th class="px-4 py-3">Usuarios</th>
          <th class="px-4 py-3">Estado</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($areas as $area)
          <tr>
            <td class="px-4 py-3">
              <p class="font-semibold text-gray-900">{{ $area->name }}</p>
              @if ($area->description)
                <p class="text-xs text-gray-500">{{ $area->description }}</p>
              @endif
            </td>
            <td class="px-4 py-3 text-gray-700">{{ $area->users_count }}</td>
            <td class="px-4 py-3">
              @if ($area->isActive())
                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Activa</span>
              @else
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Suspendida</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.areas.edit', $area) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Editar</a>

                <form method="POST" action="{{ route('admin.areas.status', $area) }}"
                      data-confirm="{{ $area->isActive() ? 'Los '.$area->users_count.' usuario(s) de «'.$area->name.'» no podrán iniciar sesión mientras esté suspendida.' : 'Los usuarios de «'.$area->name.'» volverán a poder iniciar sesión.' }}"
                      data-confirm-title="{{ $area->isActive() ? 'Suspender área' : 'Reactivar área' }}"
                      data-confirm-text="{{ $area->isActive() ? 'Suspender' : 'Reactivar' }}"
                      data-confirm-variant="{{ $area->isActive() ? 'warning' : 'primary' }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $area->isActive() ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                    {{ $area->isActive() ? 'Suspender' : 'Reactivar' }}
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.areas.destroy', $area) }}"
                      data-confirm="«{{ $area->name }}» dejará de aparecer en la lista. El historial de documentos se conserva."
                      data-confirm-title="Eliminar área"
                      data-confirm-text="Eliminar"
                      data-confirm-variant="danger">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Aún no hay áreas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</x-admin-layout>
