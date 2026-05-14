<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'account_reference',
        'company_number',
        'vat_number',
        'billing_email',
        'monthly_machine_rate_override',
        'gocardless_customer_id',
        'gocardless_billing_request_id',
        'gocardless_billing_request_flow_id',
        'gocardless_authorisation_url',
        'gocardless_mandate_id',
        'gocardless_mandate_status',
        'gocardless_mandate_requested_at',
        'gocardless_mandate_confirmed_at',
        'phone',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'county',
        'postcode',
        'country',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monthly_machine_rate_override' => 'decimal:2',
            'gocardless_mandate_requested_at' => 'datetime',
            'gocardless_mandate_confirmed_at' => 'datetime',
        ];
    }

    public function hasGoCardlessMandate(): bool
    {
        return filled($this->gocardless_mandate_id);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function engineers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'engineer_company')->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function sites(): HasManyThrough
    {
        return $this->hasManyThrough(Site::class, Client::class);
    }

    public function machines(): HasManyThrough
    {
        return $this->hasManyThrough(Machine::class, Client::class);
    }

    public function machineModels(): HasMany
    {
        return $this->hasMany(MachineModel::class);
    }

    public function emailSources(): HasMany
    {
        return $this->hasMany(EmailSource::class);
    }

    public function billingSnapshots(): HasMany
    {
        return $this->hasMany(BillingSnapshot::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function tonerAlertSetting()
    {
        return $this->hasOne(TonerAlertSetting::class);
    }
}
