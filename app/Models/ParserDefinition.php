<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ParserDefinition extends Model
{
    public const ENGINE_SHARP_MX = 'sharp_mx_status_email';

    public const ENGINE_GENERIC_COUNTER = 'generic_counter_email';

    protected $fillable = [
        'name',
        'parser_key',
        'engine_type',
        'default_configuration',
        'notes',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_configuration' => 'array',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function engines(): array
    {
        return [
            self::ENGINE_SHARP_MX => 'Sharp MX status email engine',
            self::ENGINE_GENERIC_COUNTER => 'Generic counter email engine',
        ];
    }

    public static function builtInDefinitions(): array
    {
        return [
            self::ENGINE_SHARP_MX => 'Sharp MX status email',
            self::ENGINE_GENERIC_COUNTER => 'Generic counter email',
        ];
    }

    public static function findActiveByKey(string $key): ?self
    {
        return self::query()->where('parser_key', $key)->where('is_active', true)->first();
    }

    public static function normaliseKey(string $key): string
    {
        return Str::of($key)->trim()->lower()->replaceMatches('/[^a-z0-9_]+/', '_')->trim('_')->toString();
    }
}
