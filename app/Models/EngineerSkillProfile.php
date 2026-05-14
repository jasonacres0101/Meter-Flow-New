<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineerSkillProfile extends Model
{
    public const LEVEL_NONE = 'none';

    public const LEVEL_BASIC = 'basic';

    public const LEVEL_ADVANCED = 'advanced';

    public const LEVELS = [
        self::LEVEL_NONE => 'None',
        self::LEVEL_BASIC => 'Basic',
        self::LEVEL_ADVANCED => 'Advanced',
    ];

    public const LEVEL_RANKS = [
        self::LEVEL_NONE => 0,
        self::LEVEL_BASIC => 1,
        self::LEVEL_ADVANCED => 2,
    ];

    public static function meets(?string $engineerLevel, ?string $requiredLevel): bool
    {
        return self::LEVEL_RANKS[$engineerLevel ?? self::LEVEL_NONE] >= self::LEVEL_RANKS[$requiredLevel ?? self::LEVEL_NONE];
    }

    protected $fillable = [
        'user_id',
        'networking_level',
        'vlan_level',
        'dhcp_static_ip_level',
        'dns_level',
        'routing_level',
        'firewall_level',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
