<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Usuarios de desarrollo. Es idempotente: se puede ejecutar varias veces.
     * La contraseña solo se asigna al crear; si el usuario ya existe no se toca.
     * ⚠ Cambia estas credenciales antes de usar la app con datos reales.
     */
    public function run(): void
    {


        $users = [
            ['Admin User', 'admin@gmail.com', UserRole::Admin, null],
            
        ];

        foreach ($users as [$name, $email, $role, $areaId]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->fill([
                'name' => $name,
                'role' => $role,
                'status' => 'active',
                'area_id' => $areaId,
            ]);

            if (! $user->exists) {
                $user->password = 'Admin1234*'; // se hashea por el cast 'hashed'
            }

            $user->save();
        }
    }
}
