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
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'name']);
        });

        Schema::table('machine_models', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['manufacturer', 'model_name']);
            $table->unique(['company_id', 'manufacturer', 'model_name'], 'machine_models_company_model_unique');
        });

        Schema::table('incoming_report_emails', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'parse_status']);
        });

        Schema::table('report_templates', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'reading_date']);
        });

        Schema::table('consumable_readings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'reading_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumable_readings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('incoming_report_emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('machine_models', function (Blueprint $table) {
            $table->dropUnique('machine_models_company_model_unique');
            $table->unique(['manufacturer', 'model_name']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
