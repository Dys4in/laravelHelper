<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('code');

            $table->foreignId('template_id')
                ->nullable()
                ->after('area_id')
                ->constrained('templates')
                ->nullOnDelete();

            // Copia del nombre de la plantilla utilizada.
            $table->string('template_name')->nullable();

            $table->string('file_path')->nullable();

            // Tamaño del archivo en bytes.
            $table->unsignedBigInteger('file_size')->default(0);

            // "key" que OnlyOffice utiliza para identificar
            // la sesión/version del documento.
            $table->string('doc_key', 64)->nullable();

            // Versión del documento.
            $table->unsignedInteger('version')->default(1);

            // Usuario que realizó la última actualización.
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('last_saved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('template_id');

            $table->dropColumn([
                'title',
                'template_name',
                'file_path',
                'file_size',
                'doc_key',
                'version',
                'last_saved_at'
            ]);
        });
    }
};