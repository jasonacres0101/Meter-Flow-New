<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_service_agreement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_agreement_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['machine_id', 'service_agreement_id'], 'machine_service_agreement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_service_agreement');
    }
};
