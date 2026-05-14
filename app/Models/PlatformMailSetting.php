<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMailSetting extends Model
{
    public const PROVIDER_OFFICE365 = 'office365';

    protected $fillable = [
        'provider',
        'from_name',
        'from_email',
        'oauth_tenant_id',
        'oauth_client_id',
        'oauth_client_secret',
        'oauth_scope',
        'is_active',
        'last_tested_at',
        'last_success_at',
        'last_error',
    ];

    protected $hidden = [
        'oauth_client_secret',
    ];

    protected function casts(): array
    {
        return [
            'oauth_client_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return self::query()->latest('id')->first();
    }

    public function isReady(): bool
    {
        return $this->is_active
            && filled($this->from_email)
            && filled($this->oauth_tenant_id)
            && filled($this->oauth_client_id)
            && filled($this->oauth_client_secret);
    }
}
