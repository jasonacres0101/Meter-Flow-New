<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('agreement_number');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('mono_ppc', 8, 3)->nullable();
            $table->decimal('colour_ppc', 8, 3)->nullable();
            $table->unsignedInteger('included_mono_pages')->nullable();
            $table->unsignedInteger('included_colour_pages')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'client_id', 'site_id', 'machine_id', 'starts_on'], 'service_agreements_scope_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_agreements');
    }
};
