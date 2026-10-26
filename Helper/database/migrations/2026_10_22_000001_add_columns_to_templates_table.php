<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('name', 150)->after('id');
            $table->string('description')->nullable()->after('name');
            // null = disponible para todas las áreas
            $table->foreignId('area_id')->nullable()->after('description')->constrained('areas')->nullOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('area_id');
            $table->dropSoftDeletes();
            $table->dropColumn(['name', 'description', 'file_path', 'original_name', 'file_size', 'status']);
        });
    }
};
