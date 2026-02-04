<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('domain_batches')->onDelete('set null');
            $table->string('domain_name');
            $table->enum('status', ['pending', 'purchased', 'dns_set', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('dns_set_at')->nullable();
            $table->timestamps();

            $table->index('domain_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
