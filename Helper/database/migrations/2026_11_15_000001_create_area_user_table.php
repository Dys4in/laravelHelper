<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_user', function (Blueprint $table) {
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['area_id', 'user_id']);
        });
        // Conservar las asignaciones existentes durante la actualización.
        DB::table('users')->where('role', 'mod')->whereNotNull('area_id')
            ->orderBy('id')->chunk(200, function ($users) {
                DB::table('area_user')->insert($users->map(fn ($user) => [
                    'area_id' => $user->area_id, 'user_id' => $user->id,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_user');
    }
};
