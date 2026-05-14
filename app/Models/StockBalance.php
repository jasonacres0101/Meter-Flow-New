<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $fillable = [
        'company_id',
        'stock_product_id',
        'site_id',
        'quantity',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
