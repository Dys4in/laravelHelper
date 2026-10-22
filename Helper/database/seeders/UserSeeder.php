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
        $verificaciones = Area::where('name', 'Verificaciones')->first();
        $validaciones = Area::where('name', 'Validaciones')->first();

        $users = [
            ['Admin User', 'adminUser', 'admin@gmail.com', UserRole::Admin, null],
            ['Mod User', 'moderUser', 'mod@gmail.com', UserRole::Mod, $verificaciones?->id],
            ['User', 'user', 'user@gmail.com', UserRole::User, $validaciones?->id],
        ];

        foreach ($users as [$name, $username, $email, $role, $areaId]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->fill([
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'status' => 'active',
                'area_id' => $areaId,
            ]);

            if (! $user->exists) {
                $user->password = '12345678'; // se hashea por el cast 'hashed'
            }

            $user->save();
        }
    }
}
