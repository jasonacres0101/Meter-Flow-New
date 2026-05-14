<?php

namespace App\Models;

use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'company_id',
        'incoming_report_email_id',
        'reading_date',
        'total_counter',
        'mono_counter',
        'colour_counter',
        'copy_mono_counter',
        'copy_colour_counter',
        'print_mono_counter',
        'print_colour_counter',
        'scan_counter',
        'fax_sent_counter',
        'fax_received_counter',
        'current_status',
        'paper_tray_status',
        'service_status',
        'usage_unknown',
        'counter_reset_detected',
    ];

    protected function casts(): array
    {
        return [
            'reading_date' => 'datetime',
            'paper_tray_status' => 'array',
            'usage_unknown' => 'boolean',
            'counter_reset_detected' => 'boolean',
        ];
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
