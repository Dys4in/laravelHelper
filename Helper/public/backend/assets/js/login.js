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


    if (passwordInput && passwordToggle) {

        passwordToggle.addEventListener('click', function () {

            if (passwordInput.type === 'password') {

                passwordInput.type = 'text';

                passwordToggle.src = '/backend/assets/images/avatar/passwordBlock.png';

            } else {

                passwordInput.type = 'password';

                passwordToggle.src = '/backend/assets/images/avatar/passwordBlock.png';

            }

        });

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


