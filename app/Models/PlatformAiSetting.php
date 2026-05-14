<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAiSetting extends Model
{
    public const PROVIDER_OPENAI = 'openai';

    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'base_url',
        'timeout',
        'is_active',
        'last_tested_at',
        'last_success_at',
        'last_error',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'timeout' => 'integer',
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
            && filled($this->api_key)
            && filled($this->model)
            && filled($this->base_url);
    }
}
