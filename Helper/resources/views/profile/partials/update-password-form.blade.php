<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">
            Seguridad
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Usa una contraseña larga y difícil de adivinar para mantener tu cuenta segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 max-w-md space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700">Contraseña actual </label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @if ($errors->updatePassword->has('current_password')) !border-red-400 @endif">
            @if ($errors->updatePassword->has('current_password'))
                <p class="mt-1 text-xs text-red-600">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700">Nueva contraseña </label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @if ($errors->updatePassword->has('password')) !border-red-400 @endif">
            @if ($errors->updatePassword->has('password'))
                <p class="mt-1 text-xs text-red-600">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nueva contraseña </label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        </div>

        <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-5">
            <button type="submit" class="hp-btn-primary">Actualizar contraseña</button>

            @if (session('status') === 'password-updated')
                <p class="text-sm font-medium text-green-600">Contraseña actualizada.</p>
            @endif
        </div>
    </form>
</section>
