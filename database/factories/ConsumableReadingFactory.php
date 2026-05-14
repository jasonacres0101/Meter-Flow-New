<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ConsumableReading;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumableReading>
 */
class ConsumableReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'machine_id' => Machine::factory(),
            'incoming_report_email_id' => null,
            'consumable_type' => 'toner',
            'colour' => fake()->randomElement(['black', 'cyan', 'magenta', 'yellow']),
            'percentage' => fake()->numberBetween(1, 100),
            'status' => 'NORMAL',
            'reading_date' => now(),
        ];
    }
}
