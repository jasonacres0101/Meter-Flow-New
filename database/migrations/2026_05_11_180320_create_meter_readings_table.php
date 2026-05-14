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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incoming_report_email_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reading_date');
            $table->unsignedBigInteger('total_counter')->nullable();
            $table->unsignedBigInteger('mono_counter')->nullable();
            $table->unsignedBigInteger('colour_counter')->nullable();
            $table->unsignedBigInteger('copy_mono_counter')->nullable();
            $table->unsignedBigInteger('copy_colour_counter')->nullable();
            $table->unsignedBigInteger('print_mono_counter')->nullable();
            $table->unsignedBigInteger('print_colour_counter')->nullable();
            $table->unsignedBigInteger('scan_counter')->nullable();
            $table->unsignedBigInteger('fax_sent_counter')->nullable();
            $table->unsignedBigInteger('fax_received_counter')->nullable();
            $table->string('current_status')->nullable();
            $table->json('paper_tray_status')->nullable();
            $table->string('service_status')->nullable();
            $table->boolean('usage_unknown')->default(false);
            $table->boolean('counter_reset_detected')->default(false);
            $table->timestamps();

            $table->unique(['machine_id', 'reading_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
