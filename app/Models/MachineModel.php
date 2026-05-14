<?php

namespace App\Models;

use Database\Factories\MachineModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineModel extends Model
{
    /** @use HasFactory<MachineModelFactory> */
    use HasFactory;

    protected $fillable = [
        'manufacturer',
        'manufacturer_id',
        'company_id',
        'model_name',
        'parser_type',
        'notes',
    ];

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class);
    }

    public function stockProducts(): BelongsToMany
    {
        return $this->belongsToMany(StockProduct::class)->withTimestamps();
    }

    public function manufacturerRecord(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
