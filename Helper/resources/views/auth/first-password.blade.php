<x-guest-layout>

    {{-- Login CSS --}}
    <link
        rel="stylesheet"
        href="{{ asset('backend/assets/css/login.css') }}"
    >

    <main class="login-page">

        <section class="login-card">

            <div class="login-card-glow"></div>

            <header class="login-header">

                <div class="login-logo-wrapper">
                    <img
                        src="{{ asset('backend/assets/images/avatar/transp_logo.png') }}"
                        alt="Helper"
                        class="login-logo"
                    >
                </div>

                <h1 class="login-title">
                    Establece tu contraseña
                </h1>

                <p class="login-subtitle">
                    Por seguridad, debes cambiar la contraseña temporal antes de usar HELPER.
                </p>

            </header>

            <form
                method="POST"
                action="{{ route('password.first.update') }}"
                class="login-form"
                id="firstPasswordForm"
            >

                @csrf

                {{-- Requisitos --}}
                <div class="password-requirements">
                    <strong>Requisitos de la contraseña</strong>
                    <ul>
                        <li>Mínimo 8 caracteres (10 para roles privilegiados).</li>
                        <li>Mayúsculas, minúsculas, números y símbolos.</li>
                        <li>Sin espacios ni 3 caracteres seguidos de tu nombre o usuario.</li>
                        <li>Evita datos personales o familiares.</li>
                    </ul>
                </div>

                {{-- Contraseña temporal --}}
                <div class="login-field">

                    <x-input-label
                        for="current_password"
                        :value="__('Contraseña temporal')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper login-input-wrapper--password">

                        <x-text-input
                            id="current_password"
                            type="password"
                            name="current_password"
                            required
                            autofocus
                            autocomplete="current-password"
                            placeholder="••••••"
                            :class="$errors->has('current_password') ? 'login-input login-input--error' : 'login-input'"
                        />

                        <button
                            type="button"
                            class="password-toggle"
                            data-click-guard-ignore
                            data-password-toggle="current_password"
                            aria-label="Mostrar contraseña"
                        >
                            <img
                                src="{{ asset('backend/assets/images/avatar/user-lock.png') }}"
                                alt="Mostrar contraseña"
                            >
                        </button>

                    </div>

                    @error('current_password')
                        <span class="login-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Nueva contraseña --}}
                <div class="login-field">

                    <x-input-label
                        for="password"
                        :value="__('Nueva contraseña')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper login-input-wrapper--password">

                        <x-text-input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••"
                            :class="$errors->has('password') ? 'login-input login-input--error' : 'login-input'"
                        />

                        <button
                            type="button"
                            class="password-toggle"
                            data-click-guard-ignore
                            data-password-toggle="password"
                            aria-label="Mostrar contraseña"
                        >
                            <img
                                src="{{ asset('backend/assets/images/avatar/user-lock.png') }}"
                                alt="Mostrar contraseña"
                            >
                        </button>

                    </div>

                    @error('password')
                        <span class="login-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Confirmación --}}
                <div class="login-field">

                    <x-input-label
                        for="password_confirmation"
                        :value="__('Confirmar contraseña')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper login-input-wrapper--password">

                        <x-text-input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••"
                            :class="$errors->has('password_confirmation') ? 'login-input login-input--error' : 'login-input'"
                        />

                        <button
                            type="button"
                            class="password-toggle"
                            data-click-guard-ignore
                            data-password-toggle="password_confirmation"
                            aria-label="Mostrar contraseña"
                        >
                            <img
                                src="{{ asset('backend/assets/images/avatar/user-lock.png') }}"
                                alt="Mostrar contraseña"
                            >
                        </button>

                    </div>

                    @error('password_confirmation')
                        <span class="login-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="login-button"
                    id="firstPasswordButton"
                >
                    Guardar contraseña y continuar
                </button>

            </form>

            <div class="register-link">
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>

        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const eyeIcon = new Image();
            eyeIcon.src = '/backend/assets/images/avatar/user-lock.png';

            const eyeOffIcon = new Image();
            eyeOffIcon.src = '/backend/assets/images/avatar/passwordblock.png';

            document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    const input = document.getElementById(toggle.dataset.passwordToggle);
                    const icon = toggle.querySelector('img');

                    if (!input || !icon) {
                        return;
                    }

                    const visible = input.type === 'password';

                    input.type = visible ? 'text' : 'password';
                    icon.src = visible
                        ? '/backend/assets/images/avatar/passwordblock.png'
                        : '/backend/assets/images/avatar/user-lock.png';
                    toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                });
            });

            const form = document.getElementById('firstPasswordForm');
            const button = document.getElementById('firstPasswordButton');

            if (form && button) {
                form.addEventListener('submit', function () {
                    button.disabled = true;
                    button.innerHTML = `
                        <img
                            src="/backend/assets/images/gift/infinitoload.gif"
                            class="login-loading-gif"
                            alt="Espere..."
                        >
                    `;
                });
            }

        });
    </script>

</x-guest-layout>
