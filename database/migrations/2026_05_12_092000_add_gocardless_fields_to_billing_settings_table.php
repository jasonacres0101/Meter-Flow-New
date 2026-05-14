<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_settings', function (Blueprint $table) {
            $table->boolean('gocardless_enabled')->default(false)->after('payment_terms_days');
            $table->string('gocardless_environment')->default('sandbox')->after('gocardless_enabled');
            $table->text('gocardless_access_token')->nullable()->after('gocardless_environment');
            $table->text('gocardless_webhook_secret')->nullable()->after('gocardless_access_token');
            $table->string('gocardless_creditor_id')->nullable()->after('gocardless_webhook_secret');
            $table->timestamp('gocardless_last_tested_at')->nullable()->after('gocardless_creditor_id');
            $table->timestamp('gocardless_last_success_at')->nullable()->after('gocardless_last_tested_at');
            $table->text('gocardless_last_error')->nullable()->after('gocardless_last_success_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_settings', function (Blueprint $table) {
            $table->dropColumn([
                'gocardless_enabled',
                'gocardless_environment',
                'gocardless_access_token',
                'gocardless_webhook_secret',
                'gocardless_creditor_id',
                'gocardless_last_tested_at',
                'gocardless_last_success_at',
                'gocardless_last_error',
            ]);
        });
    }
};
