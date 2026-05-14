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
            $table->decimal('mono_ppc', 8, 3)->default(0)->after('phone');
            $table->decimal('colour_ppc', 8, 3)->default(0)->after('mono_ppc');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->decimal('mono_ppc_override', 8, 3)->nullable()->after('contact_email');
            $table->decimal('colour_ppc_override', 8, 3)->nullable()->after('mono_ppc_override');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->decimal('mono_ppc_override', 8, 3)->nullable()->after('expected_report_sender_email');
            $table->decimal('colour_ppc_override', 8, 3)->nullable()->after('mono_ppc_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['mono_ppc_override', 'colour_ppc_override']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['mono_ppc_override', 'colour_ppc_override']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['mono_ppc', 'colour_ppc']);
        });
    }
};
