<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->json('palladium_config')->nullable()->after('tracking_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->dropColumn('palladium_config');
        });
    }
};
