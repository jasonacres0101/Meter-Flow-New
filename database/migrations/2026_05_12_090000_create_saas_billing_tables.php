<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_machine_rate', 10, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->unsignedTinyInteger('snapshot_day')->default(25);
            $table->unsignedTinyInteger('payment_terms_days')->default(14);
            $table->timestamps();
        });

        Schema::create('billing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('snapshot_date');
            $table->unsignedInteger('active_machine_count');
            $table->decimal('monthly_machine_rate', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->timestamps();
            $table->unique(['company_id', 'period_start', 'period_end'], 'billing_snapshots_company_period_unique');
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('active_machine_count');
            $table->decimal('monthly_machine_rate', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['company_id', 'period_start', 'period_end'], 'billing_invoices_company_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_snapshots');
        Schema::dropIfExists('billing_settings');
    }
};
