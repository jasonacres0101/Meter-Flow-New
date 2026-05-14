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
        Schema::create('incoming_report_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_email');
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text');
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->index();
            $table->json('raw_payload')->nullable();
            $table->json('parsed_payload')->nullable();
            $table->string('parse_status')->default('pending')->index();
            $table->text('parse_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_report_emails');
    }
};
