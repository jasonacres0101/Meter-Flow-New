<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->city().' Office',
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'latitude' => fake()->latitude(49.9, 58.6),
            'longitude' => fake()->longitude(-7.5, 1.8),
            'contact_email' => fake()->optional()->companyEmail(),
            'mono_ppc_override' => null,
            'colour_ppc_override' => null,
            'included_mono_pages_override' => null,
            'included_colour_pages_override' => null,
            'is_active' => true,
        ];
    }
}
