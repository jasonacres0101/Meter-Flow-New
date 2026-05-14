<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillingSnapshot extends Model
{
    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'snapshot_date',
        'active_machine_count',
        'monthly_machine_rate',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'snapshot_date' => 'date',
            'monthly_machine_rate' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(BillingInvoice::class);
    }
}
