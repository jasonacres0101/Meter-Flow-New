<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parser_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('parser_key')->unique();
            $table->string('engine_type');
            $table->json('default_configuration')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parser_definitions');
    }
};
