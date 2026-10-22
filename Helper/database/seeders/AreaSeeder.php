<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Verificaciones', 'Validaciones'] as $name) {
            Area::firstOrCreate(['name' => $name], ['status' => Area::ACTIVE]);
        }
    }
}
