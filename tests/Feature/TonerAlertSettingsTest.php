<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TonerAlertSetting;
use App\Models\User;
use App\Services\TonerAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TonerAlertSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_update_toner_alert_thresholds(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->put(route('toner-alert-settings.update'), [
            'warning_threshold' => 30,
            'critical_threshold' => 12,
            'alert_black' => '1',
            'alert_cyan' => '1',
            'alert_magenta' => '1',
            'alert_yellow' => '1',
            'include_in_dashboard' => '1',
            'is_active' => '1',
            'notification_emails' => 'service@example.com, alerts@example.com',
        ])->assertRedirect(route('toner-alert-settings.edit', ['company_id' => $company->id]));

        $this->assertDatabaseHas('toner_alert_settings', [
            'company_id' => $company->id,
            'warning_threshold' => 30,
            'critical_threshold' => 12,
        ]);
    }

    public function test_toner_alert_service_uses_company_thresholds(): void
    {
        $company = Company::factory()->create();
        TonerAlertSetting::factory()->for($company)->create([
            'warning_threshold' => 35,
            'critical_threshold' => 15,
        ]);

        $service = app(TonerAlertService::class);

        $this->assertSame('NORMAL', $service->statusFor($company->id, 40, 'black'));
        $this->assertSame('LOW', $service->statusFor($company->id, 25, 'black'));
        $this->assertSame('CRITICAL', $service->statusFor($company->id, 10, 'black'));
    }

    public function test_regular_company_user_cannot_edit_toner_alert_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('toner-alert-settings.edit'))->assertForbidden();
    }
}
