<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\TonerAlertSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TonerAlertSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $this->companyFor($request);
        $setting = TonerAlertSetting::firstOrCreate(
            ['company_id' => $company->id],
            TonerAlertSetting::defaults($company->id)->getAttributes(),
        );

        return view('toner-alert-settings.edit', [
            'setting' => $setting,
            'companies' => $request->user()->isPlatformAdmin() ? Company::orderBy('name')->get() : collect([$company]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->companyFor($request);

        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'warning_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'critical_threshold' => ['required', 'integer', 'min:1', 'max:100', 'lt:warning_threshold'],
            'alert_black' => ['nullable', 'boolean'],
            'alert_cyan' => ['nullable', 'boolean'],
            'alert_magenta' => ['nullable', 'boolean'],
            'alert_yellow' => ['nullable', 'boolean'],
            'include_in_dashboard' => ['nullable', 'boolean'],
            'notification_emails' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TonerAlertSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'warning_threshold' => $data['warning_threshold'],
                'critical_threshold' => $data['critical_threshold'],
                'alert_black' => $request->boolean('alert_black'),
                'alert_cyan' => $request->boolean('alert_cyan'),
                'alert_magenta' => $request->boolean('alert_magenta'),
                'alert_yellow' => $request->boolean('alert_yellow'),
                'include_in_dashboard' => $request->boolean('include_in_dashboard'),
                'notification_emails' => $this->emails($data['notification_emails'] ?? ''),
                'is_active' => $request->boolean('is_active', true),
            ],
        );

        return redirect()->route('toner-alert-settings.edit', ['company_id' => $company->id])->with('status', 'Toner alert settings updated.');
    }

    private function companyFor(Request $request): Company
    {
        if ($request->user()->isPlatformAdmin()) {
            return Company::findOrFail($request->integer('company_id') ?: Company::query()->orderBy('name')->value('id'));
        }

        return $request->user()->company;
    }

    /**
     * @return array<int, string>
     */
    private function emails(string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', $value))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->values()
            ->all();
    }
}
