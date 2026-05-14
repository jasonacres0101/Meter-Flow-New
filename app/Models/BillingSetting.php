<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    protected $fillable = [
        'monthly_machine_rate',
        'currency',
        'snapshot_day',
        'payment_terms_days',
        'gocardless_enabled',
        'gocardless_environment',
        'gocardless_access_token',
        'gocardless_webhook_secret',
        'gocardless_creditor_id',
        'gocardless_last_tested_at',
        'gocardless_last_success_at',
        'gocardless_last_error',
    ];

    protected $hidden = [
        'gocardless_access_token',
        'gocardless_webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'monthly_machine_rate' => 'decimal:2',
            'snapshot_day' => 'integer',
            'payment_terms_days' => 'integer',
            'gocardless_enabled' => 'boolean',
            'gocardless_access_token' => 'encrypted',
            'gocardless_webhook_secret' => 'encrypted',
            'gocardless_last_tested_at' => 'datetime',
            'gocardless_last_success_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'monthly_machine_rate' => 0,
            'currency' => 'GBP',
            'snapshot_day' => 25,
            'payment_terms_days' => 14,
            'gocardless_enabled' => false,
            'gocardless_environment' => 'sandbox',
        ]);
    }

    public function gocardlessBaseUrl(): string
    {
        return $this->gocardless_environment === 'live'
            ? 'https://api.gocardless.com'
            : 'https://api-sandbox.gocardless.com';
    }

    public function gocardlessIsReady(): bool
    {
        return $this->gocardless_enabled
            && filled($this->gocardless_environment)
            && filled($this->gocardless_access_token);
    }
}
