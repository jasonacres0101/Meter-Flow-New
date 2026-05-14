<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'company_id',
        'billing_snapshot_id',
        'invoice_number',
        'period_start',
        'period_end',
        'invoice_date',
        'due_date',
        'active_machine_count',
        'monthly_machine_rate',
        'subtotal',
        'tax_total',
        'total',
        'currency',
        'status',
        'gocardless_payment_id',
        'gocardless_payment_status',
        'gocardless_payment_error',
        'gocardless_charge_date',
        'gocardless_payment_requested_at',
        'gocardless_payment_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'monthly_machine_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'gocardless_charge_date' => 'date',
            'gocardless_payment_requested_at' => 'datetime',
            'gocardless_payment_confirmed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(BillingSnapshot::class, 'billing_snapshot_id');
    }
}
