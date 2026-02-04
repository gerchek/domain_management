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
        Schema::table('site_projects', function (Blueprint $table) {
            $table->string('black_site_archive_path')->nullable()->after('storage_path')->comment('Path to black site archive (3146_offer_archive.zip)');
            $table->enum('site_type', ['white', 'black', 'both'])->default('white')->after('black_site_archive_path')->comment('Type of site: white (for bots), black (for users), both');
            $table->json('macros')->nullable()->after('site_type')->comment('Macros to replace: domain, khost, kapitoken, allowcountry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_projects', function (Blueprint $table) {
            $table->dropColumn(['black_site_archive_path', 'site_type', 'macros']);
        });
    }
};
