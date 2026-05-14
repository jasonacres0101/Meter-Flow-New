<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketTimeLog extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'user_id',
        'started_at',
        'stopped_at',
        'duration_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
