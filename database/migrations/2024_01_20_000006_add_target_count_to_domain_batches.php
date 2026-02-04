<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_batches', function (Blueprint $table) {
            // Сколько доменов байер хочет купить (null = все из списка)
            $table->integer('target_count')->nullable()->after('total_domains');
        });
    }

    public function down(): void
    {
        Schema::table('domain_batches', function (Blueprint $table) {
            $table->dropColumn('target_count');
        });
    }
};
