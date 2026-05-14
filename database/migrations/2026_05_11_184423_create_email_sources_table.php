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
        Schema::create('email_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->string('mailbox_email');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('imap_host')->nullable();
            $table->unsignedInteger('imap_port')->nullable();
            $table->string('encryption')->nullable();
            $table->string('folder')->default('INBOX');
            $table->boolean('mark_as_seen')->default(true);
            $table->boolean('delete_after_ingest')->default(false);
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'provider', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_sources');
    }
};
