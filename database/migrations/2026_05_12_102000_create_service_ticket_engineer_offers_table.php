<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_engineer_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['service_ticket_id', 'user_id'], 'ticket_engineer_offer_unique');
            $table->index(['user_id', 'withdrawn_at', 'accepted_at'], 'ticket_engineer_offer_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_engineer_offers');
    }
};
