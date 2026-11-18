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
            // Verificaciones
            ['Mod Verificaciones', 'modVerif', 'mod@gmail.com', UserRole::Mod, $verificaciones?->id],
            ['User Verificaciones', 'userVerif', 'user@gmail.com', UserRole::User, $verificaciones?->id],
            // Validaciones (para comprobar que cada área solo ve lo suyo)
            ['Mod Validaciones', 'modValid', 'mod2@gmail.com', UserRole::Mod, $validaciones?->id],
            ['User Validaciones', 'userValid', 'user2@gmail.com', UserRole::User, $validaciones?->id],
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
