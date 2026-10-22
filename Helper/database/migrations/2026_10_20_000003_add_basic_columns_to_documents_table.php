<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas mínimas que ya espera el dashboard (code, status) más
     * el dueño y el área. En el siguiente paso se amplía (ruta del archivo, versión, etc.).
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->enum('status', ['draft', 'completed'])->default('draft')->after('code');
            $table->foreignId('user_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('user_id')->constrained('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['code', 'status']);
        });
    }
};
