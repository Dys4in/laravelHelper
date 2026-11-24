<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('managed_password_links', function (Blueprint $table) {
            $table->unsignedTinyInteger('visits_count')->default(0)->after('expires_at');
        });
    }

    public function down(): void {
        Schema::table('managed_password_links', function (Blueprint $table) {
            $table->dropColumn('visits_count');
        });
    }
};
