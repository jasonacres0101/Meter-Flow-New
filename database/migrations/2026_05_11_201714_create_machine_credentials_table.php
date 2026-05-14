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
        Schema::create('machine_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamps();
            $table->index(['machine_id', 'label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_credentials');
    }
};
