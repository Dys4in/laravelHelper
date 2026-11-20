<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un coordinador puede tener varias áreas. users.area_id sigue siendo su área principal
     * (la que se usa por defecto) y esta tabla guarda TODAS las áreas del coordinador.
     * Para usuarios y administradores no cambia nada: siguen usando solo users.area_id.
     */
    public function up(): void
    {
        Schema::create('area_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'area_id']);
        });

        // Los coordinadores que ya existen conservan su área actual.
        $now = now();

        DB::table('users')
            ->where('role', 'mod')
            ->whereNotNull('area_id')
            ->orderBy('id')
            ->get(['id', 'area_id'])
            ->map(fn ($u) => ['user_id' => $u->id, 'area_id' => $u->area_id, 'created_at' => $now, 'updated_at' => $now])
            ->chunk(500)
            ->each(fn ($chunk) => DB::table('area_user')->insert($chunk->all()));
    }

    public function down(): void
    {
        Schema::dropIfExists('area_user');
    }
};
