<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'company_id' => Company::factory(),
            'account_reference' => fake()->optional()->bothify('CLI-####'),
            'contact_email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'mono_ppc' => 0.85,
            'colour_ppc' => 4.95,
            'included_mono_pages' => 0,
            'included_colour_pages' => 0,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
