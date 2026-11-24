document.addEventListener('DOMContentLoaded', function () {

    console.log('HELPER LOGIN JS: OK');


    /*
    |--------------------------------------------------------------------------
    | Mostrar / ocultar contraseña
    |--------------------------------------------------------------------------
    */

    const passwordInput =
        document.getElementById('password');

    const passwordToggle =
        document.getElementById('passwordToggle');

    const passwordIcon =
        document.getElementById('passwordIcon');


    if (passwordInput && passwordToggle && passwordIcon) {

        const eyeIcon = new Image();
        eyeIcon.src = '/backend/assets/images/avatar/user-lock.png';

        const eyeOffIcon = new Image();
        eyeOffIcon.src = '/backend/assets/images/avatar/passwordblock.png';

        passwordToggle.addEventListener('click', function () {

            const visible = passwordInput.type === 'password';

            passwordInput.type = visible ? 'text' : 'password';

            passwordIcon.src = visible
                ? '/backend/assets/images/avatar/passwordblock.png'
                : '/backend/assets/images/avatar/user-lock.png';

            passwordToggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Remember me - Controlar autocomplete del navegador
    |--------------------------------------------------------------------------
    */

    const rememberCheckbox =
        document.getElementById('remember_me');

    if (rememberCheckbox && passwordInput) {

        function updateAutocomplete() {
            if (rememberCheckbox.checked) {
                passwordInput.setAttribute('autocomplete', 'current-password');
            } else {
                passwordInput.setAttribute('autocomplete', 'off');
            }
        }

        rememberCheckbox.addEventListener('change', updateAutocomplete);

        updateAutocomplete();

    }


    /*
    |--------------------------------------------------------------------------
    | Loading del formulario
    |--------------------------------------------------------------------------
    */

    const loginForm =
        document.getElementById('loginForm');

    const loginButton =
        document.getElementById('loginButton');


    if (loginForm && loginButton) {

        loginForm.addEventListener('submit', function () {

            loginButton.disabled = true;

            loginButton.classList.add('is-loading');

            loginButton.innerHTML = `
    <img
        src="/backend/assets/images/gift/infinitoload.gif"
        class="login-loading-gif"
        alt="calma"
    >
    
`;

        });

    }

});


