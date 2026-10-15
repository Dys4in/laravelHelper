<x-guest-layout>

    {{-- Forgot Password CSS --}}
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
                    Forgot Password
                </h1>

                <p class="login-subtitle">
                    Enter your email to receive a reset link
                </p>

            </header>

            <form
                method="POST"
                action="{{ route('password.email') }}"
                class="login-form"
                id="forgotPasswordForm"
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


                {{-- Session Status --}}
                <x-auth-session-status
                    class="login-form-status"
                    :status="session('status')"
                />

                {{-- Submit --}}
                <button
                    type="submit"
                    class="login-button"
                    id="forgotPasswordButton"
                >
                    Email Password Reset Link
                </button>

            </form>


            {{-- Back to Login --}}
            <div class="register-link">

                <span>
                    Remember your password?
                </span>

                <a href="{{ route('login') }}">
                    Sign In
                </a>

            </div>

        </section>

    </main>


    {{-- Forgot Password JS --}}
    <script src="{{ asset('backend/assets/js/login.js') }}"></script>

</x-guest-layout>
