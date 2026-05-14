<?php

namespace Database\Factories;

use App\Models\IncomingReportEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomingReportEmail>
 */
class IncomingReportEmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'machine_id' => null,
            'company_id' => null,
            'from_email' => fake()->safeEmail(),
            'to_email' => 'reports@example.test',
            'subject' => 'MX-2630N Status Message',
            'body_text' => 'Serial Number : '.fake()->numerify('########'),
            'body_html' => null,
            'received_at' => now(),
            'raw_payload' => [],
            'parsed_payload' => null,
            'parse_status' => IncomingReportEmail::STATUS_PENDING,
            'parse_error' => null,
        ];
    }
}
