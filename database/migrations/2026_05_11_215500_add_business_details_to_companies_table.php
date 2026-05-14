<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_number')->nullable()->after('account_reference');
            $table->string('vat_number')->nullable()->after('company_number');
            $table->string('website')->nullable()->after('phone');
            $table->string('address_line_1')->nullable()->after('website');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('county')->nullable()->after('city');
            $table->string('postcode')->nullable()->after('county');
            $table->string('country')->nullable()->after('postcode');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'company_number',
                'vat_number',
                'website',
                'address_line_1',
                'address_line_2',
                'city',
                'county',
                'postcode',
                'country',
            ]);
        });
    }
};
