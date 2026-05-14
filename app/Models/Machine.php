<?php

namespace App\Models;

use Database\Factories\MachineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Machine extends Model
{
    /** @use HasFactory<MachineFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'site_id',
        'machine_model_id',
        'manufacturer',
        'model',
        'serial_number',
        'machine_name',
        'location',
        'ip_address',
        'hostname',
        'mac_address',
        'subnet_mask',
        'gateway',
        'primary_dns',
        'secondary_dns',
        'network_vlan',
        'snmp_version',
        'snmp_community',
        'dhcp_enabled',
        'network_notes',
        'required_networking_level',
        'required_vlan_level',
        'required_dhcp_static_ip_level',
        'required_dns_level',
        'required_routing_level',
        'required_firewall_level',
        'expected_report_sender_email',
        'mono_ppc_override',
        'colour_ppc_override',
        'included_mono_pages_override',
        'included_colour_pages_override',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'dhcp_enabled' => 'boolean',
            'mono_ppc_override' => 'decimal:3',
            'colour_ppc_override' => 'decimal:3',
            'included_mono_pages_override' => 'integer',
            'included_colour_pages_override' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function machineModel(): BelongsTo
    {
        return $this->belongsTo(MachineModel::class);
    }

    public function incomingReportEmails(): HasMany
    {
        return $this->hasMany(IncomingReportEmail::class);
    }

    public function latestIncomingReportEmail(): HasOne
    {
        return $this->hasOne(IncomingReportEmail::class)->latestOfMany('received_at');
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function consumableReadings(): HasMany
    {
        return $this->hasMany(ConsumableReading::class);
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(MachineCredential::class);
    }

    public function serviceAgreements(): HasMany
    {
        return $this->hasMany(ServiceAgreement::class);
    }

    public function agreements(): BelongsToMany
    {
        return $this->belongsToMany(ServiceAgreement::class, 'machine_service_agreement')->withTimestamps();
    }
}
