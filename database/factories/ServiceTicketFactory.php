<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceTicket>
 */
class ServiceTicketFactory extends Factory
{
    public function definition(): array
    {
        $machine = Machine::factory()->create();

        return [
            'company_id' => $machine->client->company_id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => User::factory(),
            'assigned_engineer_id' => null,
            'ticket_number' => 'ST-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'title' => fake()->sentence(4),
            'issue_type' => 'repair',
            'priority' => 'normal',
            'status' => ServiceTicket::STATUS_OPEN,
            'description' => fake()->paragraph(),
            'required_networking_level' => 'none',
            'required_vlan_level' => 'none',
            'required_dhcp_static_ip_level' => 'none',
            'required_dns_level' => 'none',
            'required_routing_level' => 'none',
            'required_firewall_level' => 'none',
        ];
    }
}
