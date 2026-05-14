<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->string('gocardless_payment_id')->nullable()->after('status');
            $table->string('gocardless_payment_status')->nullable()->after('gocardless_payment_id');
            $table->text('gocardless_payment_error')->nullable()->after('gocardless_payment_status');
            $table->date('gocardless_charge_date')->nullable()->after('gocardless_payment_error');
            $table->timestamp('gocardless_payment_requested_at')->nullable()->after('gocardless_charge_date');
            $table->timestamp('gocardless_payment_confirmed_at')->nullable()->after('gocardless_payment_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'gocardless_payment_id',
                'gocardless_payment_status',
                'gocardless_payment_error',
                'gocardless_charge_date',
                'gocardless_payment_requested_at',
                'gocardless_payment_confirmed_at',
            ]);
        });
    }
};
