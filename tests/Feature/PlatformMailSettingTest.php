<?php

namespace Tests\Feature;

use App\Models\PlatformMailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformMailSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_save_office_365_outbound_mail_settings(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->put(route('platform-mail-settings.update'), [
            'from_name' => 'Copier Monitor',
            'from_email' => 'copiermonitor@example.com',
            'oauth_tenant_id' => 'tenant-id',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'oauth_scope' => 'https://graph.microsoft.com/.default',
            'is_active' => '1',
        ])->assertRedirect(route('platform-mail-settings.edit'));

        $this->assertDatabaseHas('platform_mail_settings', [
            'from_email' => 'copiermonitor@example.com',
            'provider' => PlatformMailSetting::PROVIDER_OFFICE365,
            'is_active' => true,
        ]);
    }

    public function test_platform_admin_can_send_test_email_with_graph_settings(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token'], 200),
            'graph.microsoft.com/*' => Http::response(null, 202),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN, 'email' => 'admin@example.com']);
        PlatformMailSetting::create([
            'from_name' => 'Copier Monitor',
            'from_email' => 'copiermonitor@example.com',
            'oauth_tenant_id' => 'tenant-id',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'oauth_scope' => 'https://graph.microsoft.com/.default',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('platform-mail-settings.test'), [
            'test_recipient' => 'support@example.com',
        ])->assertRedirect();

        $setting = PlatformMailSetting::current();
        $this->assertNotNull($setting->last_tested_at);
        $this->assertNotNull($setting->last_success_at);
        $this->assertNull($setting->last_error);
    }
}
