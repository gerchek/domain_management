<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     * Adds performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // Add index on domains.status for filtering by status
        Schema::table('domains', function (Blueprint $table) {
            if (!$this->indexExists('domains', 'domains_status_index')) {
                $table->index('status', 'domains_status_index');
            }
            if (!$this->indexExists('domains', 'domains_buyer_status_index')) {
                $table->index(['buyer_id', 'status'], 'domains_buyer_status_index');
            }
        });

        // Add composite index on domain_deployments for common queries
        Schema::table('domain_deployments', function (Blueprint $table) {
            if (!$this->indexExists('domain_deployments', 'domain_deployments_status_index')) {
                $table->index('status', 'domain_deployments_status_index');
            }
            if (!$this->indexExists('domain_deployments', 'domain_deployments_domain_status_index')) {
                $table->index(['domain_id', 'status'], 'domain_deployments_domain_status_index');
            }
            if (!$this->indexExists('domain_deployments', 'domain_deployments_status_deployed_index')) {
                $table->index(['status', 'deployed_at'], 'domain_deployments_status_deployed_index');
            }
        });

        // Add index on site_deployments for status filtering
        Schema::table('site_deployments', function (Blueprint $table) {
            if (!$this->indexExists('site_deployments', 'site_deployments_status_index')) {
                $table->index('status', 'site_deployments_status_index');
            }
            if (!$this->indexExists('site_deployments', 'site_deployments_project_status_index')) {
                $table->index(['project_id', 'status'], 'site_deployments_project_status_index');
            }
        });

        // Add index on site_projects for status filtering
        Schema::table('site_projects', function (Blueprint $table) {
            if (!$this->indexExists('site_projects', 'site_projects_status_index')) {
                $table->index('status', 'site_projects_status_index');
            }
            if (!$this->indexExists('site_projects', 'site_projects_buyer_status_index')) {
                $table->index(['buyer_id', 'status'], 'site_projects_buyer_status_index');
            }
        });

        // Add index on domain_batches for status filtering
        Schema::table('domain_batches', function (Blueprint $table) {
            if (!$this->indexExists('domain_batches', 'domain_batches_status_index')) {
                $table->index('status', 'domain_batches_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if ($this->indexExists('domains', 'domains_status_index')) {
                $table->dropIndex('domains_status_index');
            }
            if ($this->indexExists('domains', 'domains_buyer_status_index')) {
                $table->dropIndex('domains_buyer_status_index');
            }
        });

        Schema::table('domain_deployments', function (Blueprint $table) {
            if ($this->indexExists('domain_deployments', 'domain_deployments_status_index')) {
                $table->dropIndex('domain_deployments_status_index');
            }
            if ($this->indexExists('domain_deployments', 'domain_deployments_domain_status_index')) {
                $table->dropIndex('domain_deployments_domain_status_index');
            }
            if ($this->indexExists('domain_deployments', 'domain_deployments_status_deployed_index')) {
                $table->dropIndex('domain_deployments_status_deployed_index');
            }
        });

        Schema::table('site_deployments', function (Blueprint $table) {
            if ($this->indexExists('site_deployments', 'site_deployments_status_index')) {
                $table->dropIndex('site_deployments_status_index');
            }
            if ($this->indexExists('site_deployments', 'site_deployments_project_status_index')) {
                $table->dropIndex('site_deployments_project_status_index');
            }
        });

        Schema::table('site_projects', function (Blueprint $table) {
            if ($this->indexExists('site_projects', 'site_projects_status_index')) {
                $table->dropIndex('site_projects_status_index');
            }
            if ($this->indexExists('site_projects', 'site_projects_buyer_status_index')) {
                $table->dropIndex('site_projects_buyer_status_index');
            }
        });

        Schema::table('domain_batches', function (Blueprint $table) {
            if ($this->indexExists('domain_batches', 'domain_batches_status_index')) {
                $table->dropIndex('domain_batches_status_index');
            }
        });
    }
};
