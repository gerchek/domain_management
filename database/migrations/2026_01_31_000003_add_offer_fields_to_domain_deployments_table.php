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
            $table->enum('black_type', ['palladium', 'offer'])->default('palladium')->after('white_site_type');
            $table->foreignId('palladium_config_id')->nullable()->after('black_type')->constrained('palladium_configs')->onDelete('set null');
            $table->foreignId('offer_id')->nullable()->after('palladium_config_id')->constrained('offers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_deployments', function (Blueprint $table) {
            $table->dropForeign(['palladium_config_id']);
            $table->dropForeign(['offer_id']);
            $table->dropColumn(['black_type', 'palladium_config_id', 'offer_id']);
        });
    }
};
