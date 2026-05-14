<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformMailSetting;
use App\Models\User;
use Throwable;

class PlatformMailer
{
    public function __construct(private readonly MicrosoftGraphMailClient $graph)
    {
    }

    public function sendAccountCreated(Company $company, User $admin, string $temporaryPassword): bool
    {
        $setting = PlatformMailSetting::current();

        if (! $setting?->isReady()) {
            return false;
        }

        $loginUrl = route('login');
        $html = view('emails.account-created', [
            'company' => $company,
            'admin' => $admin,
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => $loginUrl,
        ])->render();

        try {
            $this->graph->sendFromPlatform(
                $setting,
                $admin->email,
                "Your {$company->name} Copier Monitor account",
                $html
            );

            $setting->update([
                'last_success_at' => now(),
                'last_error' => null,
            ]);

            return true;
        } catch (Throwable $exception) {
            $setting->update([
                'last_error' => $exception->getMessage(),
            ]);

            report($exception);

            return false;
        }
    }
}
