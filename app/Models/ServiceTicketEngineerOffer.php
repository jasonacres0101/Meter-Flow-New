<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketEngineerOffer extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'user_id',
        'accepted_at',
        'declined_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'withdrawn_at' => 'datetime',
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
