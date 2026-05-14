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
        Schema::create('service_ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->text('notes')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_ticket_updates');
    }
};
