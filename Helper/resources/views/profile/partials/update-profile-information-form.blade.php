<section class="space-y-6">
    <header class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-lg text-brand">
            <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
        </span>
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-gray-900">
                Información personal
            </h2>

            <p class="mt-1 text-sm leading-relaxed text-gray-600">
                Actualiza tu nombre; la firma y el correo son de solo lectura.
            </p>
        </div>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="max-w-2xl space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Nombre completo <span class="text-brand"></span>
            </label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" maxlength="255"
                   class="mt-1.5 block h-11 w-full rounded-lg border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm transition focus:border-brand focus:ring-brand @error('name') !border-red-400 @enderror">
            <p class="mt-1.5 text-xs text-gray-500">Así aparecerá tu nombre en documentos y registros.</p>
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="block text-sm font-medium text-gray-700">Firma GMP</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        Solo lectura
                    </span>
                </div>
                <div class="mt-1.5 flex h-11 w-full items-center truncate rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700">{{ $user->signature ?: '—' }}</div>
                <p class="mt-1.5 text-xs text-gray-500">La registró el administrador.</p>
            </div>

            <div>
                <div class="flex items-center justify-between gap-2">
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        Solo lectura
                    </span>
                </div>
                <input id="email" type="email" value="{{ $user->email }}" readonly
                       class="mt-1.5 block h-11 w-full cursor-not-allowed truncate rounded-lg border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 focus:border-gray-200 focus:ring-0">
                <p class="mt-1.5 text-xs text-gray-500">Se gestiona desde administración.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-xs text-gray-500">Los cambios se aplican de inmediato.</p>
            <button type="submit" class="hp-btn-outline">
                <i class="bi bi-check2 mr-2" aria-hidden="true"></i>
                Guardar cambios
            </button>
        </div>
    </form>
</section>
