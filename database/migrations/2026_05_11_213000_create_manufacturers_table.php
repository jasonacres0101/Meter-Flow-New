<?php

use App\Models\Manufacturer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('machine_models', function (Blueprint $table) {
            $table->foreignId('manufacturer_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->index(['manufacturer_id', 'model_name']);
        });

        DB::table('machine_models')
            ->select('manufacturer')
            ->whereNotNull('manufacturer')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer')
            ->each(function (string $name): void {
                $manufacturer = Manufacturer::findOrCreateByName($name);

                DB::table('machine_models')
                    ->where('manufacturer', $name)
                    ->update(['manufacturer_id' => $manufacturer->id]);
            });

        Schema::table('machine_models', function (Blueprint $table) {
            $table->dropUnique('machine_models_company_model_unique');
            $table->unique(['company_id', 'manufacturer_id', 'model_name'], 'machine_models_company_manufacturer_model_unique');
        });
    }

    public function down(): void
    {
        Schema::table('machine_models', function (Blueprint $table) {
            $table->dropUnique('machine_models_company_manufacturer_model_unique');
            $table->unique(['company_id', 'manufacturer', 'model_name'], 'machine_models_company_model_unique');
            $table->dropForeign(['manufacturer_id']);
            $table->dropIndex(['manufacturer_id', 'model_name']);
            $table->dropColumn('manufacturer_id');
        });

        Schema::dropIfExists('manufacturers');
    }
};
