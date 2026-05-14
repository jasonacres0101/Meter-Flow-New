<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Tenant
{
    public static function scope(Builder $query, ?User $user, string $column = 'company_id'): Builder
    {
        if (! $user || $user->isPlatformAdmin()) {
            return $query;
        }

        if ($user->isEngineer()) {
            return $query->where($column, self::activeCompanyId($user));
        }

        return $query->where($column, $user->company_id);
    }

    public static function scopeWithGlobal(Builder $query, ?User $user, string $column = 'company_id'): Builder
    {
        if (! $user || $user->isPlatformAdmin()) {
            return $query;
        }

        $companyId = $user->isEngineer() ? self::activeCompanyId($user) : $user->company_id;

        return $query->where(fn (Builder $query) => $query
            ->where($column, $companyId)
            ->orWhereNull($column));
    }

    public static function companyId(?User $user): ?int
    {
        if (! $user || $user->isPlatformAdmin()) {
            return null;
        }

        return self::activeCompanyId($user);
    }

    public static function activeCompanyId(User $user): ?int
    {
        if ($user->isEngineer()) {
            $ids = self::accessibleCompanies($user)->pluck('id');
            $sessionCompany = session('active_company_id');

            return $ids->contains($sessionCompany) ? (int) $sessionCompany : $ids->first();
        }

        return $user->company_id;
    }

    public static function accessibleCompanies(User $user): Collection
    {
        if ($user->isPlatformAdmin()) {
            return Company::orderBy('name')->get();
        }

        if ($user->isEngineer()) {
            return $user->engineerCompanies()->orderBy('name')->get();
        }

        return collect([$user->company])->filter();
    }
}
