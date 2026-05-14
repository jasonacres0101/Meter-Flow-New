<?php

namespace App\Services;

use App\Models\TonerAlertSetting;

class TonerAlertService
{
    public function settingFor(?int $companyId): TonerAlertSetting
    {
        if (! $companyId) {
            return TonerAlertSetting::defaults();
        }

        return TonerAlertSetting::query()->where('company_id', $companyId)->first()
            ?: TonerAlertSetting::defaults($companyId);
    }

    public function statusFor(?int $companyId, ?int $percentage, ?string $colour = null): string
    {
        return $this->settingFor($companyId)->statusFor($percentage, $colour);
    }
}
