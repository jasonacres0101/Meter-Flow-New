<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketCompletionReview extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'user_id',
        'machine_snapshot',
        'verified_fields',
        'functional_checks',
        'resolution',
    ];

    protected function casts(): array
    {
        return [
            'machine_snapshot' => 'array',
            'verified_fields' => 'array',
            'functional_checks' => 'array',
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
