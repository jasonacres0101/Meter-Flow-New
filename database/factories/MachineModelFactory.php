<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineModel>
 */
class MachineModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $manufacturer = Manufacturer::findOrCreateByName(fake()->randomElement(['Sharp', 'Canon', 'Ricoh', 'Konica Minolta']));

        return [
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'company_id' => Company::factory(),
            'model_name' => fake()->bothify('MX-####N'),
            'parser_type' => fake()->randomElement(['sharp_mx_status_email', 'generic_counter_email']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
