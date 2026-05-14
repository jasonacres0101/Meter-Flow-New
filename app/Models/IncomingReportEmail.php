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

    public function extractedSerialNumber(): ?string
    {
        return preg_match('/(?:\[serial(?:\s+number| no\.?)?\]|serial(?:\s+number| no\.?)?)\s*[:,=|]\s*([A-Z0-9-]+)/i', $this->body_text, $matches)
            ? trim($matches[1])
            : null;
    }
}
