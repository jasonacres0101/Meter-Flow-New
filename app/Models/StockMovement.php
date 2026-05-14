<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_ADD = 'add';

    public const TYPE_TRANSFER_TO_SITE = 'transfer_to_site';

    protected $fillable = [
        'company_id',
        'stock_product_id',
        'from_site_id',
        'to_site_id',
        'created_by_user_id',
        'movement_type',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'stock_product_id');
    }

    public function fromSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'from_site_id');
    }

    public function toSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'to_site_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
