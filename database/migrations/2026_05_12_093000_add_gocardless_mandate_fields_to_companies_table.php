<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gocardless_customer_id')->nullable()->after('monthly_machine_rate_override');
            $table->string('gocardless_billing_request_id')->nullable()->after('gocardless_customer_id');
            $table->string('gocardless_billing_request_flow_id')->nullable()->after('gocardless_billing_request_id');
            $table->text('gocardless_authorisation_url')->nullable()->after('gocardless_billing_request_flow_id');
            $table->string('gocardless_mandate_id')->nullable()->after('gocardless_authorisation_url');
            $table->string('gocardless_mandate_status')->nullable()->after('gocardless_mandate_id');
            $table->timestamp('gocardless_mandate_requested_at')->nullable()->after('gocardless_mandate_status');
            $table->timestamp('gocardless_mandate_confirmed_at')->nullable()->after('gocardless_mandate_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'gocardless_customer_id',
                'gocardless_billing_request_id',
                'gocardless_billing_request_flow_id',
                'gocardless_authorisation_url',
                'gocardless_mandate_id',
                'gocardless_mandate_status',
                'gocardless_mandate_requested_at',
                'gocardless_mandate_confirmed_at',
            ]);
        });
    }
};
