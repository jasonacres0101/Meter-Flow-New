<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'account_reference',
        'contact_email',
        'phone',
        'mono_ppc',
        'colour_ppc',
        'included_mono_pages',
        'included_colour_pages',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mono_ppc' => 'decimal:3',
            'colour_ppc' => 'decimal:3',
            'included_mono_pages' => 'integer',
            'included_colour_pages' => 'integer',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceAgreements(): HasMany
    {
        return $this->hasMany(ServiceAgreement::class);
    }
}
