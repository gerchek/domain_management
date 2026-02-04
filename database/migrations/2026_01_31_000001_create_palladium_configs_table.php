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
        Schema::create('palladium_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('geo')->nullable()->comment('Countries: us,es,de etc');
            $table->unsignedBigInteger('client_id');
            $table->string('client_company');
            $table->text('client_secret');
            $table->string('banner_source')->default('adwords');
            $table->string('file_path')->nullable()->comment('Path to uploaded PHP file');
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('palladium_configs');
    }
};
