<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('required_networking_level')->default('none')->after('description');
            $table->string('required_vlan_level')->default('none')->after('required_networking_level');
            $table->string('required_dhcp_static_ip_level')->default('none')->after('required_vlan_level');
            $table->string('required_dns_level')->default('none')->after('required_dhcp_static_ip_level');
            $table->string('required_routing_level')->default('none')->after('required_dns_level');
            $table->string('required_firewall_level')->default('none')->after('required_routing_level');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'required_networking_level',
                'required_vlan_level',
                'required_dhcp_static_ip_level',
                'required_dns_level',
                'required_routing_level',
                'required_firewall_level',
            ]);
        });
    }
};
