<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TonerAlertSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TonerAlertSetting>
 */
class TonerAlertSettingFactory extends Factory
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
            'warning_threshold' => 25,
            'critical_threshold' => 10,
            'alert_black' => true,
            'alert_cyan' => true,
            'alert_magenta' => true,
            'alert_yellow' => true,
            'include_in_dashboard' => true,
            'notification_emails' => [],
            'is_active' => true,
        ];
    }
}
