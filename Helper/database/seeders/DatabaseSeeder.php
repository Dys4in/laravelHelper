<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AreaSeeder::class,   // primero las áreas
            UserSeeder::class,   // luego los usuarios que las usan
        ]);
    }
}
