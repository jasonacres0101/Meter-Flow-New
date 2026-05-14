<?php

namespace Tests\Feature;

use App\Models\PlatformAiSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformAiSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_save_ai_settings(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $longServiceAccountKey = 'sk-svcacct-'.str_repeat('a', 780);

        $this->actingAs($admin)->put(route('platform-ai-settings.update'), [
            'api_key' => $longServiceAccountKey,
            'model' => 'gpt-test-parser',
            'base_url' => 'https://api.openai.com/v1',
            'timeout' => 45,
            'is_active' => '1',
        ])->assertRedirect(route('platform-ai-settings.edit'));

        $setting = PlatformAiSetting::current();

        $this->assertNotNull($setting);
        $this->assertSame('gpt-test-parser', $setting->model);
        $this->assertSame($longServiceAccountKey, $setting->api_key);
        $this->assertNotSame($longServiceAccountKey, $setting->getRawOriginal('api_key'));
    }

    public function test_platform_admin_can_test_ai_settings(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'parser_type' => 'generic_counter_email',
                    'parser_configuration' => ['magenta_toner_percentage_labels' => ['Magenta Toner']],
                    'explanation' => 'Mapped toner fields.',
                    'confidence_score' => 82,
                ]),
            ], 200),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        PlatformAiSetting::create([
            'provider' => PlatformAiSetting::PROVIDER_OPENAI,
            'api_key' => 'sk-test-key',
            'model' => 'gpt-test-parser',
            'base_url' => 'https://api.openai.com/v1',
            'timeout' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('platform-ai-settings.test'))
            ->assertRedirect()
            ->assertSessionHas('status', 'AI test completed. Parser suggestions are ready.');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-test-key')
            && $request['model'] === 'gpt-test-parser');

        $setting = PlatformAiSetting::current();
        $this->assertNotNull($setting->last_tested_at);
        $this->assertNotNull($setting->last_success_at);
        $this->assertNull($setting->last_error);
    }

    public function test_company_user_cannot_edit_ai_settings(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($user)->get(route('platform-ai-settings.edit'))->assertForbidden();
    }
}
