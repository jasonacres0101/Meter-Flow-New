<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineCredential>
 */
class MachineCredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'created_by_user_id' => User::factory(),
            'label' => 'Device admin',
            'username' => 'admin',
            'password' => 'secret-password',
            'url' => fake()->url(),
            'notes' => fake()->optional()->sentence(),
            'last_rotated_at' => now(),
        ];
    }
}
