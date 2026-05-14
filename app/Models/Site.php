<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'latitude',
        'longitude',
        'contact_email',
        'mono_ppc_override',
        'colour_ppc_override',
        'included_mono_pages_override',
        'included_colour_pages_override',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'mono_ppc_override' => 'decimal:3',
            'colour_ppc_override' => 'decimal:3',
            'included_mono_pages_override' => 'integer',
            'included_colour_pages_override' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function serviceAgreements(): HasMany
    {
        return $this->hasMany(ServiceAgreement::class);
    }
}
