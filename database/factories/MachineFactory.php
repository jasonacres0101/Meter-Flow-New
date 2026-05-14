<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();
        $client = Client::factory()->for($company);
        $site = Site::factory()->for($client);
        $model = MachineModel::factory()->for($company);

        return [
            'client_id' => $client,
            'site_id' => $site,
            'machine_model_id' => $model,
            'manufacturer' => 'Sharp',
            'model' => 'MX-2630N',
            'serial_number' => fake()->unique()->numerify('########'),
            'machine_name' => fake()->words(2, true),
            'location' => fake()->randomElement(['Reception', 'Accounts', 'Warehouse', 'Sales']),
            'ip_address' => fake()->ipv4(),
            'hostname' => fake()->optional()->domainWord().'-mfp',
            'mac_address' => fake()->macAddress(),
            'subnet_mask' => '255.255.255.0',
            'gateway' => fake()->ipv4(),
            'primary_dns' => '8.8.8.8',
            'secondary_dns' => '1.1.1.1',
            'network_vlan' => fake()->optional()->numberBetween(10, 200),
            'snmp_version' => 'v2c',
            'snmp_community' => 'public',
            'dhcp_enabled' => false,
            'network_notes' => null,
            'required_networking_level' => 'basic',
            'required_vlan_level' => 'none',
            'required_dhcp_static_ip_level' => 'basic',
            'required_dns_level' => 'none',
            'required_routing_level' => 'none',
            'required_firewall_level' => 'none',
            'expected_report_sender_email' => fake()->safeEmail(),
            'mono_ppc_override' => null,
            'colour_ppc_override' => null,
            'included_mono_pages_override' => null,
            'included_colour_pages_override' => null,
            'is_active' => true,
        ];
    }
}
