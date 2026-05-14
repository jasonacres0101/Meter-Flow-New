<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parser_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_report_email_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('scope')->nullable();
            $table->string('parser_type')->nullable();
            $table->json('parser_configuration')->nullable();
            $table->timestamps();

            $table->index(['incoming_report_email_id', 'action'], 'parser_logs_email_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parser_review_logs');
    }
};
