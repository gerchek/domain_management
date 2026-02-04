<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatgpt_models', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "GPT-4 Turbo")
            $table->string('model_id'); // API model ID (e.g., "gpt-4-turbo")
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatgpt_models');
    }
};
