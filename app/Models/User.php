<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    public const ROLE_PLATFORM_ADMIN = 'platform_admin';

    public const ROLE_COMPANY_ADMIN = 'company_admin';

    public const ROLE_COMPANY_USER = 'company_user';

    public const ROLE_ENGINEER = 'engineer';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'email',
        'role',
        'is_active',
        'password',
        'engineer_pin',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'engineer_pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function engineerCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'engineer_company')->withTimestamps();
    }

    public function engineerSkillProfile(): HasOne
    {
        return $this->hasOne(EngineerSkillProfile::class);
    }

    public function supportedManufacturers(): BelongsToMany
    {
        return $this->belongsToMany(Manufacturer::class, 'engineer_manufacturer')
            ->withPivot('skill_level')
            ->withTimestamps();
    }

    public function assignedServiceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'assigned_engineer_id');
    }

    public function serviceTicketOffers(): HasMany
    {
        return $this->hasMany(ServiceTicketEngineerOffer::class);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === self::ROLE_PLATFORM_ADMIN;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === self::ROLE_COMPANY_ADMIN;
    }

    public function isEngineer(): bool
    {
        return $this->role === self::ROLE_ENGINEER;
    }

    public function hasEngineerPin(): bool
    {
        return filled($this->engineer_pin);
    }

    public function setEngineerPin(string $pin): void
    {
        $this->forceFill(['engineer_pin' => Hash::make($pin)])->save();
    }

    public function engineerPinMatches(string $pin): bool
    {
        return $this->hasEngineerPin() && Hash::check($pin, $this->engineer_pin);
    }

    public function canManageCompanyUsers(): bool
    {
        return $this->isPlatformAdmin() || $this->isCompanyAdmin();
    }
}
