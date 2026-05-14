<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name', 'type', 'supplier'], 'stock_products_company_name_type_supplier_unique');
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['stock_product_id', 'site_id'], 'stock_balances_product_site_unique');
            $table->index(['company_id', 'site_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('to_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type');
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_products');
    }
};
