<?php

namespace App\Models;

use Database\Factories\ConsumableReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableReading extends Model
{
    /** @use HasFactory<ConsumableReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'company_id',
        'incoming_report_email_id',
        'consumable_type',
        'colour',
        'percentage',
        'status',
        'reading_date',
    ];

    protected function casts(): array
    {
        return ['reading_date' => 'datetime'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function incomingReportEmail(): BelongsTo
    {
        return $this->belongsTo(IncomingReportEmail::class);
    }
}
