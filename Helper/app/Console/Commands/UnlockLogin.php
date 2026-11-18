<?php

namespace App\Console\Commands;

use App\Support\LoginThrottle;
use Illuminate\Console\Command;

class UnlockLogin extends Command
{
    protected $signature = 'login:unlock {email : Correo bloqueado por intentos fallidos}';

    protected $description = 'Quita el bloqueo de 30 minutos por contraseñas incorrectas de un correo.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $left = LoginThrottle::lockedFor($email);

        LoginThrottle::clear($email);

        $this->info($left > 0
            ? "Se desbloqueó {$email} (faltaban ".LoginThrottle::humanize($left).').'
            : "{$email} no estaba bloqueado; se reinició su conteo de intentos.");

        return self::SUCCESS;
    }
}
