<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_model_stock_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_model_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['stock_product_id', 'machine_model_id'], 'stock_product_machine_model_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_model_stock_product');
    }
};
