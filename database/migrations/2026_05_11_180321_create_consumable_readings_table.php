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
        Schema::create('consumable_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incoming_report_email_id')->nullable()->constrained()->nullOnDelete();
            $table->string('consumable_type');
            $table->string('colour')->nullable();
            $table->unsignedTinyInteger('percentage')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('reading_date');
            $table->timestamps();

            $table->unique(['machine_id', 'consumable_type', 'colour', 'reading_date'], 'consumable_unique_reading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_readings');
    }
};
