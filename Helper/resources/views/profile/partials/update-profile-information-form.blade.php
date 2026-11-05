<section class="flex h-full flex-col">
    <header class="border-b border-gray-100 pb-5">
        <h2 class="text-base font-semibold text-gray-900">
            Información personal
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Edita tu nombre correspondiente a tu firma
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 flex flex-1 flex-col gap-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Nombre completo <span class="text-brand"></span>
            </label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" maxlength="255"
                   class="mt-1.5 block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm transition focus:border-brand focus:ring-brand @error('name') !border-red-400 @enderror">
            <p class="mt-1.5 text-xs text-gray-500">Así aparecerá tu nombre en documentos y registros.</p>
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <label for="short_name" class="block text-sm font-medium text-gray-700">Firma</label>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        Solo lectura
                    </span>
                </div>
                <input id="short_name" type="text" value="{{ $user->short_name }}" readonly
                       class="mt-1.5 block w-full cursor-not-allowed rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500 focus:border-gray-200 focus:ring-0">
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
                       class="mt-1.5 block w-full cursor-not-allowed rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500 focus:border-gray-200 focus:ring-0">
            </div>
        </div>

        <div class="mt-auto flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">Los cambios se aplican de inmediato.</p>
            <button type="submit" class="hp-btn-outline w-full sm:w-auto">
                <i class="bi bi-check2 mr-2" aria-hidden="true"></i>
                Guardar cambios
            </button>
        </div>
    </form>
</section>
