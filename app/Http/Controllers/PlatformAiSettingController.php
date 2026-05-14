<?php

namespace App\Http\Controllers;

use App\Models\PlatformAiSetting;
use App\Services\Reports\AiParserSuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PlatformAiSettingController extends Controller
{
    public function edit(): View
    {
        return view('platform-ai-settings.edit', [
            'setting' => PlatformAiSetting::current(),
            'envConfigured' => filled(config('services.openai.key')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = PlatformAiSetting::current();
        $data = $request->validate([
            'api_key' => [$setting ? 'nullable' : 'required', 'string', 'max:500'],
            'model' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'url', 'max:255'],
            'timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $data['provider'] = PlatformAiSetting::PROVIDER_OPENAI;
        $data['is_active'] = $request->boolean('is_active', true);

        if ($setting) {
            $setting->update($data);
        } else {
            PlatformAiSetting::create($data);
        }

        return redirect()->route('platform-ai-settings.edit')->with('status', 'AI settings saved.');
    }

    public function test(AiParserSuggestionService $suggestions): RedirectResponse
    {
        $setting = PlatformAiSetting::current();
        abort_unless($setting?->isReady() || filled(config('services.openai.key')), 422, 'Save active OpenAI settings before testing.');

        $setting?->update(['last_tested_at' => now()]);

        try {
            $suggestions->suggest(<<<'EMAIL'
Serial Number: TEST-001
Magenta Toner|38%|OK
Waste Toner Container||OK
EMAIL);

            $setting?->update(['last_success_at' => now(), 'last_error' => null]);

            return back()->with('status', 'AI test completed. Parser suggestions are ready.');
        } catch (Throwable $exception) {
            $setting?->update(['last_error' => $exception->getMessage()]);

            return back()->withErrors(['test' => 'AI test failed: '.$exception->getMessage()]);
        }
    }
}
