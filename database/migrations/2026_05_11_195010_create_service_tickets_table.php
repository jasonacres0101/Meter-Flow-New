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
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->string('issue_type')->default('repair');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->text('description');
            $table->timestamp('requested_for')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};
