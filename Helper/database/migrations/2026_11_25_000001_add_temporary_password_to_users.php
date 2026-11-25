<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Contraseña temporal (cifrada) para que el admin pueda volver a verla hasta que la persona la cambie. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('temporary_password')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('temporary_password'));
    }
};
