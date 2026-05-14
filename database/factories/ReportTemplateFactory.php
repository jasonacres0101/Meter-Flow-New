<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MachineModel;
use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
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
            'machine_model_id' => MachineModel::factory(),
            'template_name' => 'Default status email',
            'family_key' => 'default_status_email',
            'version' => 1,
            'sample_subject' => 'MX-2630N Status Message',
            'sample_body' => 'Serial Number : '.fake()->numerify('########').PHP_EOL.'Total Counter'.PHP_EOL.fake()->numberBetween(1000, 999999),
            'parser_type' => 'sharp_mx_status_email',
            'parser_configuration' => [],
            'is_active' => true,
            'approval_status' => ReportTemplate::STATUS_COMPANY,
        ];
    }
}
