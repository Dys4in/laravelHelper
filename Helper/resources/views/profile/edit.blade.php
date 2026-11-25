@php($areas = $user->assignedAreas())

<x-panel-layout title="Mi perfil">
  <header class="flex flex-col gap-1">
    <p class="hp-overview-sub">Consulta los datos de tu cuenta y actualiza tu información personal.</p>
  </header>

  <div class="grid items-start gap-6 lg:grid-cols-2">
    {{-- Perfil --}}
    <section class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex items-start gap-4">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-400 via-brand to-purple-700 text-2xl font-bold text-white shadow-md ring-4 ring-brand-soft">
          {{ $user->initial }}
        </div>

        <div class="min-w-0 flex-1 pt-1">
          <p class="truncate text-lg font-bold text-gray-900">{{ $user->short_name }}</p>
          <p class="break-words text-sm font-medium text-gray-600">{{ $user->name }}</p>
          <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
        </div>
      </div>

      <div class="mt-7 border-t border-gray-100 pt-2">
        <dl class="divide-y divide-gray-100">
          <div class="flex items-center justify-between gap-4 py-4">
            <dt class="flex items-center gap-2 text-sm text-gray-500">
              <i class="bi bi-person-badge text-base text-brand" aria-hidden="true"></i>
              Rol
            </dt>
            <dd class="text-sm font-semibold text-gray-900">{{ $user->role->label() }}</dd>
          </div>

          <div class="flex items-start justify-between gap-4 py-4">
            <dt class="flex items-center gap-2 text-sm text-gray-500">
              <i class="bi bi-diagram-3 text-base text-brand" aria-hidden="true"></i>
              {{ $areas->count() > 1 ? 'Áreas' : 'Área' }}
            </dt>
            @if ($areas->isEmpty())
              <dd class="text-sm font-semibold text-gray-900">Sin área</dd>
            @elseif ($areas->count() === 1)
              <dd class="text-right text-sm font-semibold text-gray-900">{{ $areas->first()->name }}</dd>
            @else
              <dd class="flex max-w-[65%] flex-wrap justify-end gap-1.5">
                @foreach ($areas as $area)
                  <span class="rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand">{{ $area->name }}</span>
                @endforeach
              </dd>
            @endif
          </div>

          <div class="flex items-center justify-between gap-4 py-4">
            <dt class="flex items-center gap-2 text-sm text-gray-500">
              <i class="bi bi-shield-check text-base text-brand" aria-hidden="true"></i>
              Estado de la cuenta
            </dt>
            <dd>
              <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $user->isActive() ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                <i class="bi bi-circle-fill text-[7px]" aria-hidden="true"></i>
                {{ $user->isActive() ? 'Activo' : 'Inactivo' }}
              </span>
            </dd>
          </div>
        </dl>
      </div>

      <p class="pt-6 text-xs leading-relaxed text-gray-400">
        La firma GMP se muestra en Información personal y no se puede editar. El correo electrónico se gestiona desde la administración del sistema.
      </p>
    </section>

    {{-- Información personal --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
      @include('profile.partials.update-profile-information-form')
    </section>
  </div>

  @if (session('status') === 'profile-updated')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.confirmDialog) {
          confirmDialog({
            alert: true,
            title: 'Cambios guardados',
            message: 'Tu información personal se actualizó correctamente.',
            confirmText: 'Aceptar',
            variant: 'primary'
          });
        }
      });
    </script>
  @elseif (session('status') === 'profile-unchanged')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.confirmDialog) {
          confirmDialog({
            alert: true,
            title: 'Sin cambios',
            message: 'No hay cambios para guardar.',
            confirmText: 'Aceptar',
            variant: 'primary'
          });
        }
      });
    </script>
  @endif
</x-panel-layout>
