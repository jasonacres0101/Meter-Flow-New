<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('office365');
            $table->string('from_name')->default('Copier Monitor');
            $table->string('from_email');
            $table->string('oauth_tenant_id');
            $table->string('oauth_client_id');
            $table->text('oauth_client_secret');
            $table->string('oauth_scope')->default('https://graph.microsoft.com/.default');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_mail_settings');
    }
};
