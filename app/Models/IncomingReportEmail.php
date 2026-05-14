<?php

namespace App\Models;

use Database\Factories\IncomingReportEmailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingReportEmail extends Model
{
    /** @use HasFactory<IncomingReportEmailFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_TEMPLATE = 'pending_template';

    public const STATUS_PARSED = 'parsed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNMATCHED = 'unmatched';

    protected $fillable = [
        'machine_id',
        'company_id',
        'from_email',
        'to_email',
        'subject',
        'body_text',
        'body_html',
        'received_at',
        'raw_payload',
        'parsed_payload',
        'parse_status',
        'parse_error',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'raw_payload' => 'array',
            'parsed_payload' => 'array',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function extractedSerialNumber(): ?string
    {
        return preg_match('/(?:\[serial(?:\s+number| no\.?)?\]|serial(?:\s+number| no\.?)?)\s*[:,=|]\s*([A-Z0-9-]+)/i', $this->body_text, $matches)
            ? trim($matches[1])
            : null;
    }

    public function customerStatusLabel(): string
    {
        return match ($this->parse_status) {
            self::STATUS_PARSED => 'Reporting active',
            self::STATUS_PENDING_TEMPLATE => 'Setup in progress',
            self::STATUS_FAILED => 'Support review needed',
            self::STATUS_UNMATCHED => 'Waiting for machine match',
            default => 'Processing',
        };
    }

    public function customerStatusTone(): string
    {
        return match ($this->parse_status) {
            self::STATUS_PARSED => 'bg-emerald-50 text-emerald-700',
            self::STATUS_PENDING_TEMPLATE => 'bg-amber-50 text-amber-800',
            self::STATUS_FAILED => 'bg-rose-50 text-rose-700',
            self::STATUS_UNMATCHED => 'bg-blue-50 text-blue-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function technicalStatusLabel(): string
    {
        return str_replace('_', ' ', $this->parse_status);
    }
}
