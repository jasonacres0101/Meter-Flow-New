<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicketUpdate extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'user_id',
        'status',
        'scheduled_for',
        'notes',
        'resolution',
    ];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ServiceTicketPhoto::class);
    }
}
