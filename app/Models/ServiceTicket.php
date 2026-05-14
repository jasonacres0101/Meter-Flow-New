<?php

namespace App\Models;

use Database\Factories\ServiceTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicket extends Model
{
    /** @use HasFactory<ServiceTicketFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'client_id',
        'site_id',
        'machine_id',
        'opened_by_user_id',
        'assigned_engineer_id',
        'ticket_number',
        'title',
        'issue_type',
        'priority',
        'status',
        'description',
        'required_networking_level',
        'required_vlan_level',
        'required_dhcp_static_ip_level',
        'required_dns_level',
        'required_routing_level',
        'required_firewall_level',
        'requested_for',
        'scheduled_for',
        'resolved_at',
        'resolution',
    ];

    protected function casts(): array
    {
        return [
            'requested_for' => 'datetime',
            'scheduled_for' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ServiceTicketUpdate::class);
    }

    public function engineerOffers(): HasMany
    {
        return $this->hasMany(ServiceTicketEngineerOffer::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ServiceTicketTimeLog::class);
    }

    public function completionReviews(): HasMany
    {
        return $this->hasMany(ServiceTicketCompletionReview::class);
    }
}
