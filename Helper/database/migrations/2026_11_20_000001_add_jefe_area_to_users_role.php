<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'jefe_de_area', 'mod', 'user') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("UPDATE users SET role = 'user' WHERE role = 'jefe_de_area'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'mod', 'user') NOT NULL DEFAULT 'user'");
    }
};
