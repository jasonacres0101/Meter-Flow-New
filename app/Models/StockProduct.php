<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockProduct extends Model
{
    public const TYPE_TONER = 'toner';

    public const TYPE_PAPER = 'paper';

    public const TYPE_WASTE_BOX = 'waste_box';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'supplier',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_TONER => 'Toner',
            self::TYPE_PAPER => 'Paper',
            self::TYPE_WASTE_BOX => 'Waste boxes',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function machineModels(): BelongsToMany
    {
        return $this->belongsToMany(MachineModel::class)->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
