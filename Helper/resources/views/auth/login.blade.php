<x-guest-layout>

    {{-- Login CSS --}}
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
                    Welcome
                </h1>

                <p class="login-subtitle">
                    Please enter your details to sign in
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
                            autofocus
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
            autocomplete="current-password"
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


                {{-- Remember / Forgot --}}
                <div class="login-options">

                    <label
                        for="remember_me"
                        class="remember-control"
                    >

                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="remember-checkbox"
                        >

                        <span>
                            Remember
                        </span>

                    </label>


                    @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="forgot-link"
                        >
                            Forgot password?
                        </a>

                    @endif

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


            {{-- Register --}}
            @if (Route::has('register'))

                <div class="register-link">

                    <span>
                        Don't have an account?
                    </span>

                    <a href="{{ route('register') }}">
                        Sign up
                    </a>

                </div>

            @endif

        </section>

    </main>


    {{-- Login JS --}}
    <script src="{{ asset('backend/assets/js/login.js') }}"></script>

</x-guest-layout>