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
        Schema::table('machines', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('ip_address');
            $table->string('mac_address')->nullable()->after('hostname');
            $table->string('subnet_mask')->nullable()->after('mac_address');
            $table->string('gateway')->nullable()->after('subnet_mask');
            $table->string('primary_dns')->nullable()->after('gateway');
            $table->string('secondary_dns')->nullable()->after('primary_dns');
            $table->string('network_vlan')->nullable()->after('secondary_dns');
            $table->string('snmp_version')->nullable()->after('network_vlan');
            $table->string('snmp_community')->nullable()->after('snmp_version');
            $table->boolean('dhcp_enabled')->default(false)->after('snmp_community');
            $table->text('network_notes')->nullable()->after('dhcp_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn([
                'hostname',
                'mac_address',
                'subnet_mask',
                'gateway',
                'primary_dns',
                'secondary_dns',
                'network_vlan',
                'snmp_version',
                'snmp_community',
                'dhcp_enabled',
                'network_notes',
            ]);
        });
    }
};
