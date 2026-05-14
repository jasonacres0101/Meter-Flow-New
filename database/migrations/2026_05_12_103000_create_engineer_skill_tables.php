<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineer_skill_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('networking_level')->default('none');
            $table->string('vlan_level')->default('none');
            $table->string('dhcp_static_ip_level')->default('none');
            $table->string('dns_level')->default('none');
            $table->string('routing_level')->default('none');
            $table->string('firewall_level')->default('none');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('engineer_manufacturer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->string('skill_level')->default('basic');
            $table->timestamps();

            $table->unique(['user_id', 'manufacturer_id'], 'engineer_manufacturer_unique');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->string('required_networking_level')->default('basic')->after('network_notes');
            $table->string('required_vlan_level')->default('basic')->after('required_networking_level');
            $table->string('required_dhcp_static_ip_level')->default('basic')->after('required_vlan_level');
            $table->string('required_dns_level')->default('basic')->after('required_dhcp_static_ip_level');
            $table->string('required_routing_level')->default('basic')->after('required_dns_level');
            $table->string('required_firewall_level')->default('basic')->after('required_routing_level');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn([
                'required_networking_level',
                'required_vlan_level',
                'required_dhcp_static_ip_level',
                'required_dns_level',
                'required_routing_level',
                'required_firewall_level',
            ]);
        });

        Schema::dropIfExists('engineer_manufacturer');
        Schema::dropIfExists('engineer_skill_profiles');
    }
};
