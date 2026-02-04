<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'removed' status to site_deployments
        DB::statement("ALTER TABLE site_deployments MODIFY COLUMN status ENUM('pending', 'deploying', 'completed', 'failed', 'removed') DEFAULT 'pending'");

        // Add 'removed' status to site_projects
        DB::statement("ALTER TABLE site_projects MODIFY COLUMN status ENUM('pending', 'generating', 'ready', 'failed', 'removed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE site_deployments MODIFY COLUMN status ENUM('pending', 'deploying', 'completed', 'failed') DEFAULT 'pending'");
        DB::statement("ALTER TABLE site_projects MODIFY COLUMN status ENUM('pending', 'generating', 'ready', 'failed') DEFAULT 'pending'");
    }
};
