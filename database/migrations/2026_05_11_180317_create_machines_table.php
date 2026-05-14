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
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_model_id')->constrained()->restrictOnDelete();
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->string('machine_name')->nullable();
            $table->string('location')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('expected_report_sender_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
