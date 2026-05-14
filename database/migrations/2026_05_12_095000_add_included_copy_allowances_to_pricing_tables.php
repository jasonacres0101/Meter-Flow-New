<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('included_mono_pages')->default(0)->after('colour_ppc');
            $table->unsignedInteger('included_colour_pages')->default(0)->after('included_mono_pages');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedInteger('included_mono_pages_override')->nullable()->after('colour_ppc_override');
            $table->unsignedInteger('included_colour_pages_override')->nullable()->after('included_mono_pages_override');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->unsignedInteger('included_mono_pages_override')->nullable()->after('colour_ppc_override');
            $table->unsignedInteger('included_colour_pages_override')->nullable()->after('included_mono_pages_override');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['included_mono_pages_override', 'included_colour_pages_override']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['included_mono_pages_override', 'included_colour_pages_override']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['included_mono_pages', 'included_colour_pages']);
        });
    }
};
