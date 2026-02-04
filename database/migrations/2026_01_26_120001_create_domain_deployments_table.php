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
        Schema::create('domain_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('site_project_id')->nullable()->constrained('site_projects')->onDelete('set null');
            $table->enum('status', ['pending', 'deployed', 'failed'])->default('pending');
            $table->string('server_host')->nullable();
            $table->string('server_path')->nullable();
            $table->json('geo_restrictions')->nullable()->comment('Allowed countries for black site');
            $table->json('deployment_log')->nullable()->comment('Logs from deployment process');
            $table->string('white_site_type')->nullable()->comment('Type: hideclick, pall, android');
            $table->string('black_site_archive')->nullable()->comment('Path to black site archive');
            $table->json('tracking_config')->nullable()->comment('Kaitaro and tracking configuration');
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('deployed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_deployments');
    }
};
