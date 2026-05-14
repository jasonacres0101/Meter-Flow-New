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
        Schema::create('service_ticket_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['service_ticket_id', 'user_id', 'stopped_at'], 'ticket_time_user_stop_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_ticket_time_logs');
    }
};
