<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_ai_settings')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('alter table `platform_ai_settings` modify `api_key` text null');
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_ai_settings')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('alter table `platform_ai_settings` modify `api_key` varchar(255) null');
    }
};
