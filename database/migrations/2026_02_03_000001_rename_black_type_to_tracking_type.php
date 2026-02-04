<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes:
     * - Rename black_type to tracking_type
     * - Change enum values: 'palladium' -> 'keitaro', 'offer' stays same
     * - Now palladium is ALWAYS used, tracking_type determines keitaro or offer
     */
    public function up(): void
    {
        // Step 1: Add new column
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->enum('tracking_type', ['keitaro', 'offer'])->default('keitaro')->after('black_type');
        });

        // Step 2: Migrate data - 'palladium' becomes 'keitaro', 'offer' stays 'offer'
        DB::table('domain_deployments')
            ->where('black_type', 'palladium')
            ->update(['tracking_type' => 'keitaro']);

        DB::table('domain_deployments')
            ->where('black_type', 'offer')
            ->update(['tracking_type' => 'offer']);

        // Step 3: Drop old column
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->dropColumn('black_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add back old column
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->enum('black_type', ['palladium', 'offer'])->default('palladium')->after('white_site_type');
        });

        // Step 2: Migrate data back - 'keitaro' becomes 'palladium', 'offer' stays 'offer'
        DB::table('domain_deployments')
            ->where('tracking_type', 'keitaro')
            ->update(['black_type' => 'palladium']);

        DB::table('domain_deployments')
            ->where('tracking_type', 'offer')
            ->update(['black_type' => 'offer']);

        // Step 3: Drop new column
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->dropColumn('tracking_type');
        });
    }
};
