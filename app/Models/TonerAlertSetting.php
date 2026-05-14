<?php

namespace App\Models;

use Database\Factories\TonerAlertSettingFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TonerAlertSetting extends Model
{
    /** @use HasFactory<TonerAlertSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warning_threshold',
        'critical_threshold',
        'alert_black',
        'alert_cyan',
        'alert_magenta',
        'alert_yellow',
        'include_in_dashboard',
        'notification_emails',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'alert_black' => 'boolean',
            'alert_cyan' => 'boolean',
            'alert_magenta' => 'boolean',
            'alert_yellow' => 'boolean',
            'include_in_dashboard' => 'boolean',
            'notification_emails' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function notificationEmails(): Attribute
    {
        return Attribute::make(
            get: function ($value): array {
                if (is_array($value)) {
                    return $value;
                }

                $decoded = is_string($value) ? json_decode($value, true) : null;

                if (is_array($decoded)) {
                    return $decoded;
                }

                return collect(preg_split('/[\r\n,]+/', (string) $value))
                    ->map(fn (string $email) => trim($email))
                    ->filter()
                    ->values()
                    ->all();
            },
        );
    }

    public static function defaults(?int $companyId = null): self
    {
        return new self([
            'company_id' => $companyId,
            'warning_threshold' => 25,
            'critical_threshold' => 10,
            'alert_black' => true,
            'alert_cyan' => true,
            'alert_magenta' => true,
            'alert_yellow' => true,
            'include_in_dashboard' => true,
            'notification_emails' => [],
            'is_active' => true,
        ]);
    }

    public function colourEnabled(?string $colour): bool
    {
        return match ($colour) {
            'black' => $this->alert_black,
            'cyan' => $this->alert_cyan,
            'magenta' => $this->alert_magenta,
            'yellow' => $this->alert_yellow,
            default => true,
        };
    }

    public function statusFor(?int $percentage, ?string $colour = null): string
    {
        if (! $this->is_active || $percentage === null || ! $this->colourEnabled($colour)) {
            return 'NORMAL';
        }

        if ($percentage <= $this->critical_threshold) {
            return 'CRITICAL';
        }

        if ($percentage <= $this->warning_threshold) {
            return 'LOW';
        }

        return 'NORMAL';
    }
}
