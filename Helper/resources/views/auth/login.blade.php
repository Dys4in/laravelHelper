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
                    Bienvenido 
                </h1>

                <p class="login-subtitle">
                    Por favor, ingresa tus datos para iniciar sesión
                </p>

            </header>

            <form
                method="POST"
                action="{{ route('login') }}"
                class="login-form"
                id="loginForm"
            >

                @csrf

                {{-- Email --}}
                <div class="login-field">

                    <x-input-label
                        for="email"
                        :value="__('Correo electrónico')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper">

                        <x-text-input
                            id="email"
                            type="email"
                            name="email"
                            :value="old('email', $rememberedEmail ?? '')"
                            required
                            :autofocus="empty($rememberedEmail)"
                            autocomplete="username"
                            placeholder="dsarah.villarica@fareva.com"
                            :class="$errors->has('email') ? 'login-input login-input--error' : 'login-input'"
                        />

                    </div>

                </div>


                {{-- Password --}}
                <div class="login-field">

                    <x-input-label
                        for="password"
                        :value="__('Contraseña')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper login-input-wrapper--password">

                        <x-text-input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="off"
                            placeholder="••••••"
                            :autofocus="! empty($rememberedEmail)"
                            :class="$errors->has('password') ? 'login-input login-input--error' : 'login-input'"
                        />

                        <button
                            type="button"
                            id="passwordToggle"
                            class="password-toggle"
                            aria-label="Mostrar contraseña"
                        >
                            <img
                                id="passwordIcon"
                                src="{{ asset('backend/assets/images/avatar/user-lock.png') }}"
                                alt="Mostrar contraseña"
                            >
                        </button>

                    </div>

                </div>


                {{-- Remember --}}
                <div class="login-options">

                    <label
                        for="remember_me"
                        class="remember-control"
                    >

                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="remember-checkbox"
                            @checked(old('remember', ! empty($rememberedEmail)))
                        >

                        <span>
                            Recordar mi correo
                        </span>

                    </label>

                </div>


                {{-- Submit --}}
                <button
                    type="submit"
                    class="login-button"
                    id="loginButton"
                >
                    Sign In
                </button>

            </form>


            {{-- Acceso solo por solicitud --}}
            <div class="register-link">
                <span>Solicítudes al administrador del sistema.</span>
            </div>
        </section>

    </main>


    {{-- Login JS --}}
    <script src="{{ asset('backend/assets/js/login.js') }}"></script>

    {{-- Remember Me Autocomplete Control --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rememberCheckbox = document.getElementById('remember_me');
            const passwordInput = document.getElementById('password');

            if (rememberCheckbox && passwordInput) {
                function updateAutocomplete() {
                    passwordInput.autocomplete = rememberCheckbox.checked ? 'current-password' : 'off';
                }

                rememberCheckbox.addEventListener('change', updateAutocomplete);
                updateAutocomplete();
            }
        });
    </script>

    <x-toast />

</x-guest-layout>