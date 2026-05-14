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
        Schema::create('machine_models', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer');
            $table->string('model_name');
            $table->string('parser_type')->default('generic_counter_email');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['manufacturer', 'model_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_models');
    }
};
