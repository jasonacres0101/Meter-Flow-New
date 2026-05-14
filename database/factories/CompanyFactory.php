<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
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
            'account_reference' => fake()->unique()->bothify('CO-####'),
            'company_number' => fake()->optional()->numerify('########'),
            'vat_number' => fake()->optional()->bothify('GB#########'),
            'billing_email' => fake()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'county' => fake()->optional()->randomElement(['Greater London', 'Greater Manchester', 'West Midlands', 'Merseyside', 'West Yorkshire', 'Kent', 'Surrey']),
            'postcode' => fake()->postcode(),
            'country' => 'United Kingdom',
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
