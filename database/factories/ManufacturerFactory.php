<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Manufacturer>
 */
class ManufacturerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Sharp', 'Canon', 'Ricoh', 'Konica Minolta', 'Xerox', 'Kyocera']).' '.fake()->unique()->bothify('##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
