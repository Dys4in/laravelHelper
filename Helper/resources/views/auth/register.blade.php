<x-guest-layout>

    {{-- Register CSS --}}
    <link
        rel="stylesheet"
        href="{{ asset('backend/assets/css/login.css') }}"
    >

    {{-- Session Status --}}
    <x-auth-session-status
        class="login-status"
        :status="session('status')"
    />

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
                    Register
                </h1>

                <p class="login-subtitle">
                    Create your account to get started
                </p>

            </header>

            <form
                method="POST"
                action="{{ route('register') }}"
                class="login-form"
                id="registerForm"
            >

                @csrf

                {{-- Name --}}
                <div class="login-field">

                    <x-input-label
                        for="name"
                        :value="__('Name')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper">

                        <x-text-input
                            id="name"
                            type="text"
                            name="name"
                            :value="old('name')"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="John Doe"
                            class="login-input"
                        />

                    </div>

                    <x-input-error
                        :messages="$errors->get('name')"
                        class="login-error"
                    />

                </div>


                {{-- Email --}}
                <div class="login-field">

                    <x-input-label
                        for="email"
                        :value="__('Email Address')"
                        class="login-label"
                    />

                    <div class="login-input-wrapper">

                        <x-text-input
                            id="email"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autocomplete="username"
                            placeholder="dsarah.villarica@fareva.com"
                            class="login-input"
                        />

                    </div>

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="login-error"
                    />

                </div>


                {{-- Password --}}
                <div class="login-field">

    <x-input-label
        for="password"
        :value="__('Password')"
        class="login-label"
    />

    <div class="login-input-wrapper">

        <x-text-input
            id="password"
            type="password"
            name="password"
            required
            autocomplete="new-password"
            placeholder="••••••"
            class="login-input"
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

    <x-input-error
        :messages="$errors->get('password')"
        class="login-error"
    />

</div>


                {{-- Confirm Password --}}
                <div class="login-field">

    <x-input-label
        for="password_confirmation"
        :value="__('Confirm Password')"
        class="login-label"
    />

    <div class="login-input-wrapper">

        <x-text-input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
            autocomplete="new-password"
            placeholder="••••••"
            class="login-input"
        />

        <button
            type="button"
            id="confirmPasswordToggle"
            class="password-toggle"
            aria-label="Mostrar confirmación de contraseña"
        >
            <img
                id="confirmPasswordIcon"
                src="{{ asset('backend/assets/images/avatar/user-lock.png') }}"
                alt="Mostrar confirmación de contraseña"
            >
        </button>

    </div>

    <x-input-error
        :messages="$errors->get('password_confirmation')"
        class="login-error"
    />

</div>


                {{-- Submit --}}
                <button
                    type="submit"
                    class="login-button"
                    id="registerButton"
                >
                    Register
                </button>

            </form>


            {{-- Login Link --}}
            <div class="register-link">

                <span>
                    Already have an account?
                </span>

                <a href="{{ route('login') }}">
                    Sign In
                </a>

            </div>

        </section>

    </main>


    {{-- Register JS --}}
    <script src="{{ asset('backend/assets/js/login.js') }}"></script>

</x-guest-layout>
