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
        Schema::create('toner_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('warning_threshold')->default(25);
            $table->unsignedTinyInteger('critical_threshold')->default(10);
            $table->boolean('alert_black')->default(true);
            $table->boolean('alert_cyan')->default(true);
            $table->boolean('alert_magenta')->default(true);
            $table->boolean('alert_yellow')->default(true);
            $table->boolean('include_in_dashboard')->default(true);
            $table->json('notification_emails')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toner_alert_settings');
    }
};
