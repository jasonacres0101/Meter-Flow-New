<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai');
            $table->text('api_key')->nullable();
            $table->string('model')->default('gpt-4.1-mini');
            $table->string('base_url')->default('https://api.openai.com/v1');
            $table->unsignedSmallInteger('timeout')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_ai_settings');
    }
};
