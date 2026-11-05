<x-panel-layout title="Mi perfil">
  <header class="flex flex-col gap-1">
    <p class="hp-overview-sub">Consulta los datos de tu cuenta y actualiza tu información personal.</p>
  </header>

  <div class="grid items-stretch gap-6 lg:grid-cols-2">
    {{-- Perfil --}}
    <section class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex items-start gap-4">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-400 via-brand to-purple-700 text-2xl font-bold text-white shadow-md ring-4 ring-brand-soft">
          {{ $user->initial }}
        </div>

        <div class="min-w-0 flex-1 pt-1">
          <p class="truncate text-lg font-bold text-gray-900">{{ $user->short_name }}</p>
          <p class="truncate text-sm font-medium text-gray-600">{{ $user->name }}</p>
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

          <div class="flex items-center justify-between gap-4 py-4">
            <dt class="flex items-center gap-2 text-sm text-gray-500">
              <i class="bi bi-diagram-3 text-base text-brand" aria-hidden="true"></i>
              Área
            </dt>
            <dd class="truncate text-sm font-semibold text-gray-900">{{ $user->area?->name ?? 'Sin área' }}</dd>
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

      <p class="mt-auto pt-4 text-xs leading-relaxed text-gray-400">
        La abreviatura y el correo electrónico se gestionan desde la administración del sistema.
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
  @endif
</x-panel-layout>
