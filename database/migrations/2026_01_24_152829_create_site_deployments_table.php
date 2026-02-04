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
        Schema::create('site_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('site_projects')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->enum('status', ['pending', 'deploying', 'completed', 'failed'])->default('pending');
            $table->boolean('ssl_installed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_deployments');
    }
};
