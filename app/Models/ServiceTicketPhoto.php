<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ServiceTicketPhoto extends Model
{
    protected $fillable = [
        'service_ticket_update_id',
        'path',
        'original_name',
        'mime_type',
    ];

    public function ticketUpdate(): BelongsTo
    {
        return $this->belongsTo(ServiceTicketUpdate::class, 'service_ticket_update_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
