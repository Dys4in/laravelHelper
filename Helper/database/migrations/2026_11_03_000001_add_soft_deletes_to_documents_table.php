<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Borrar un documento lo oculta pero conserva su historial (y el audit trail lo referencia). */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
