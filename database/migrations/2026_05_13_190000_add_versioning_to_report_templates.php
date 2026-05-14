<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->string('family_key')->nullable()->after('template_name');
            $table->unsignedInteger('version')->default(1)->after('family_key');
            $table->string('approval_status')->default('company')->after('is_active');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->index(['family_key', 'company_id', 'version']);
            $table->index(['company_id', 'approval_status']);
        });

        DB::table('report_templates')
            ->whereNull('company_id')
            ->update(['approval_status' => 'approved_global']);
    }

    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropIndex(['family_key', 'company_id', 'version']);
            $table->dropIndex(['company_id', 'approval_status']);
            $table->dropColumn(['family_key', 'version', 'approval_status', 'approved_at']);
        });
    }
};
