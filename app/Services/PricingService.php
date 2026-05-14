<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\ServiceAgreement;
use Carbon\CarbonInterface;

class PricingService
{
    public function ratesForMachine(Machine $machine, CarbonInterface|string|null $effectiveDate = null): array
    {
        $machine->loadMissing(['site', 'client']);
        $effectiveDate = $effectiveDate ? \Illuminate\Support\Carbon::parse($effectiveDate) : now();
        $agreement = $this->activeAgreementForMachine($machine, $effectiveDate);

        return [
            'mono_ppc' => $this->firstRate(
                $agreement?->mono_ppc,
                $machine->mono_ppc_override,
                $machine->site?->mono_ppc_override,
                $machine->client?->mono_ppc,
            ),
            'colour_ppc' => $this->firstRate(
                $agreement?->colour_ppc,
                $machine->colour_ppc_override,
                $machine->site?->colour_ppc_override,
                $machine->client?->colour_ppc,
            ),
            'included_mono_pages' => $this->firstRate(
                $agreement?->included_mono_pages,
                $machine->included_mono_pages_override,
                $machine->site?->included_mono_pages_override,
                $machine->client?->included_mono_pages,
            ),
            'included_colour_pages' => $this->firstRate(
                $agreement?->included_colour_pages,
                $machine->included_colour_pages_override,
                $machine->site?->included_colour_pages_override,
                $machine->client?->included_colour_pages,
            ),
            'service_agreement_number' => $agreement?->agreement_number,
            'service_agreement_starts_on' => $agreement?->starts_on?->toDateString(),
            'service_agreement_ends_on' => $agreement?->ends_on?->toDateString(),
        ];
    }

    public function revenueForUsage(Machine $machine, ?int $monoUsage, ?int $colourUsage): array
    {
        $rates = $this->ratesForMachine($machine);

        return $this->revenueForChargeableUsage($machine, $monoUsage, $colourUsage, $rates, 0, 0);
    }

    public function revenueForChargeableUsage(Machine $machine, ?int $monoUsage, ?int $colourUsage, ?array $rates = null, int $includedMono = 0, int $includedColour = 0): array
    {
        $rates ??= $this->ratesForMachine($machine);
        $monoPence = (float) $rates['mono_ppc'];
        $colourPence = (float) $rates['colour_ppc'];
        $monoUsage = $monoUsage ?? 0;
        $colourUsage = $colourUsage ?? 0;
        $chargeableMono = max(0, $monoUsage - $includedMono);
        $chargeableColour = max(0, $colourUsage - $includedColour);

        $monoRevenue = ($chargeableMono * $monoPence) / 100;
        $colourRevenue = ($chargeableColour * $colourPence) / 100;

        return [
            'mono_ppc' => $monoPence,
            'colour_ppc' => $colourPence,
            'included_mono_pages' => $includedMono,
            'included_colour_pages' => $includedColour,
            'included_total_pages' => $includedMono + $includedColour,
            'chargeable_mono_pages' => $chargeableMono,
            'chargeable_colour_pages' => $chargeableColour,
            'chargeable_total_pages' => $chargeableMono + $chargeableColour,
            'service_agreement_number' => $rates['service_agreement_number'] ?? null,
            'service_agreement_starts_on' => $rates['service_agreement_starts_on'] ?? null,
            'service_agreement_ends_on' => $rates['service_agreement_ends_on'] ?? null,
            'mono_revenue' => round($monoRevenue, 2),
            'colour_revenue' => round($colourRevenue, 2),
            'total_revenue' => round($monoRevenue + $colourRevenue, 2),
        ];
    }

    private function firstRate(mixed ...$rates): float
    {
        foreach ($rates as $rate) {
            if ($rate !== null && $rate !== '') {
                return (float) $rate;
            }
        }

        return 0;
    }

    private function activeAgreementForMachine(Machine $machine, CarbonInterface $date): ?ServiceAgreement
    {
        return ServiceAgreement::query()
            ->whereHas('machines', fn ($query) => $query->whereKey($machine->id))
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->orderByDesc('starts_on')
            ->latest('id')
            ->first();
    }
}
