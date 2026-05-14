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
        Schema::create('service_ticket_completion_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('machine_snapshot');
            $table->json('verified_fields');
            $table->json('functional_checks');
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['service_ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_ticket_completion_reviews');
    }
};
