<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceAgreement extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'site_id',
        'machine_id',
        'agreement_number',
        'starts_on',
        'ends_on',
        'mono_ppc',
        'colour_ppc',
        'included_mono_pages',
        'included_colour_pages',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'mono_ppc' => 'decimal:3',
            'colour_ppc' => 'decimal:3',
            'included_mono_pages' => 'integer',
            'included_colour_pages' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'machine_service_agreement')->withTimestamps();
    }
}
