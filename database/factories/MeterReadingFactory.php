<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Machine;
use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
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
            'reading_date' => now(),
            'total_counter' => fake()->numberBetween(1000, 999999),
            'mono_counter' => fake()->numberBetween(1000, 500000),
            'colour_counter' => fake()->numberBetween(1000, 250000),
            'current_status' => 'READY',
            'usage_unknown' => false,
            'counter_reset_detected' => false,
        ];
    }
}
