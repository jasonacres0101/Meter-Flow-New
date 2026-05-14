<?php

namespace App\Http\Controllers;

use App\Models\PlatformMailSetting;
use App\Services\MicrosoftGraphMailClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PlatformMailSettingController extends Controller
{
    public function edit(): View
    {
        return view('platform-mail-settings.edit', [
            'setting' => PlatformMailSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = PlatformMailSetting::current();
        $data = $request->validate([
            'from_name' => ['required', 'string', 'max:255'],
            'from_email' => ['required', 'email', 'max:255'],
            'oauth_tenant_id' => ['required', 'string', 'max:255'],
            'oauth_client_id' => ['required', 'string', 'max:255'],
            'oauth_client_secret' => [$setting ? 'nullable' : 'required', 'string', 'max:255'],
            'oauth_scope' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($data['oauth_client_secret'] ?? null)) {
            unset($data['oauth_client_secret']);
        }

        $data['provider'] = PlatformMailSetting::PROVIDER_OFFICE365;
        $data['oauth_scope'] = $data['oauth_scope'] ?: 'https://graph.microsoft.com/.default';
        $data['is_active'] = $request->boolean('is_active', true);

        if ($setting) {
            $setting->update($data);
        } else {
            PlatformMailSetting::create($data);
        }

        return redirect()->route('platform-mail-settings.edit')->with('status', 'Platform email settings saved.');
    }

    public function test(Request $request, MicrosoftGraphMailClient $graph): RedirectResponse
    {
        $data = $request->validate([
            'test_recipient' => ['nullable', 'email', 'max:255'],
        ]);

        $setting = PlatformMailSetting::current();
        abort_unless($setting?->isReady(), 422, 'Save active Office 365 mail settings before testing.');

        $recipient = $data['test_recipient'] ?: $request->user()->email;
        $setting->update(['last_tested_at' => now()]);

        try {
            $graph->sendFromPlatform(
                $setting,
                $recipient,
                'Copier Monitor test email',
                '<p>This is a test email from your Copier Monitor SaaS Office 365 mail settings.</p>'
            );

            $setting->update(['last_success_at' => now(), 'last_error' => null]);

            return back()->with('status', "Test email sent to {$recipient}.");
        } catch (Throwable $exception) {
            $setting->update(['last_error' => $exception->getMessage()]);

            return back()->withErrors(['test_recipient' => 'Test failed: '.$exception->getMessage()]);
        }
    }
}
