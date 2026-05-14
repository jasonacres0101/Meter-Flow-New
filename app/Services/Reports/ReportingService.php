<?php

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Machine;
use App\Models\MeterReading;
use App\Models\Site;
use App\Services\PricingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportingService
{
    public function __construct(private readonly PricingService $pricing) {}

    public function machineDailyUsage(Machine $machine, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->readingsFor($machine->meterReadings(), $from, $to)
            ->map(fn (MeterReading $reading) => $this->usageForReading($reading));
    }

    public function siteDailyUsage(Site $site, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $site->machines->flatMap(fn (Machine $machine) => $this->machineDailyUsage($machine, $from, $to))
            ->groupBy('date')
            ->map(fn (Collection $rows, string $date) => $this->summariseRows($date, $rows))
            ->values();
    }

    public function clientDailyUsage(Client $client, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $client->machines->flatMap(fn (Machine $machine) => $this->machineDailyUsage($machine, $from, $to))
            ->groupBy('date')
            ->map(fn (Collection $rows, string $date) => $this->summariseRows($date, $rows))
            ->values();
    }

    public function machineRevenue(Machine $machine, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $machine->loadMissing(['client', 'site']);
        $remainingByAgreement = [];

        return $this->machineDailyUsage($machine, $from, $to)
            ->map(function (array $row) use ($machine, &$remainingByAgreement) {
                $rates = $this->pricing->ratesForMachine($machine, $row['date']);
                $agreementKey = $rates['service_agreement_number'] ?: 'legacy-'.$machine->id;
                $remainingByAgreement[$agreementKey] ??= [
                    'mono' => (int) $rates['included_mono_pages'],
                    'colour' => (int) $rates['included_colour_pages'],
                ];
                $monoUsage = $row['mono_usage'] ?? 0;
                $colourUsage = $row['colour_usage'] ?? 0;
                $includedMono = min($remainingByAgreement[$agreementKey]['mono'], $monoUsage);
                $includedColour = min($remainingByAgreement[$agreementKey]['colour'], $colourUsage);
                $remainingByAgreement[$agreementKey]['mono'] -= $includedMono;
                $remainingByAgreement[$agreementKey]['colour'] -= $includedColour;

                return array_merge($row, $this->pricing->revenueForChargeableUsage($machine, $monoUsage, $colourUsage, $rates, $includedMono, $includedColour), [
                'machine_name' => $machine->machine_name ?? $machine->serial_number,
                'site_id' => $machine->site_id,
                'site_name' => $machine->site?->name,
                'client_id' => $machine->client_id,
                'client_name' => $machine->client?->name,
                ]);
            });
    }

    public function siteRevenue(Site $site, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $site->loadMissing(['machines.client', 'machines.site']);

        return $site->machines->flatMap(fn (Machine $machine) => $this->machineRevenue($machine, $from, $to));
    }

    public function clientRevenue(Client $client, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $client->loadMissing(['machines.client', 'machines.site']);

        return $client->machines->flatMap(fn (Machine $machine) => $this->machineRevenue($machine, $from, $to));
    }

    public function usageBetween(CarbonInterface $from, CarbonInterface $to, ?int $companyId = null): Collection
    {
        $query = MeterReading::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $this->readingsFor($query, $from, $to)
            ->map(fn (MeterReading $reading) => $this->usageForReading($reading));
    }

    public function usageForReading(MeterReading $reading): array
    {
        $previous = MeterReading::query()
            ->where('machine_id', $reading->machine_id)
            ->where('reading_date', '<', $reading->reading_date)
            ->orderByDesc('reading_date')
            ->first();

        if (! $previous || $reading->total_counter === null || $previous->total_counter === null) {
            return $this->usageRow($reading, null, null, null, true, false);
        }

        $reset = $reading->total_counter < $previous->total_counter;

        return $this->usageRow(
            $reading,
            $reset ? null : $reading->total_counter - $previous->total_counter,
            $this->counterDiff($reading->mono_counter, $previous->mono_counter),
            $this->counterDiff($reading->colour_counter, $previous->colour_counter),
            false,
            $reset,
        );
    }

    public function revenueBetween(CarbonInterface $from, CarbonInterface $to, ?int $companyId = null): Collection
    {
        $rows = $this->usageBetween($from, $to, $companyId);
        $machines = Machine::with(['client', 'site'])->whereIn('id', $rows->pluck('machine_id')->unique())->get()->keyBy('id');

        return $rows
            ->groupBy('machine_id')
            ->flatMap(function (Collection $machineRows, int $machineId) use ($machines) {
                $machine = $machines->get($machineId);

                if (! $machine) {
                    return $machineRows->map(fn (array $row) => array_merge($row, [
                        'mono_ppc' => 0,
                        'colour_ppc' => 0,
                        'included_mono_pages' => 0,
                        'included_colour_pages' => 0,
                        'included_total_pages' => 0,
                        'chargeable_mono_pages' => 0,
                        'chargeable_colour_pages' => 0,
                        'chargeable_total_pages' => 0,
                        'mono_revenue' => 0,
                        'colour_revenue' => 0,
                        'total_revenue' => 0,
                    ]));
                }

                $remainingByAgreement = [];

                return $machineRows->sortBy('date')->map(function (array $row) use ($machine, &$remainingByAgreement) {
                    $rates = $this->pricing->ratesForMachine($machine, $row['date']);
                    $agreementKey = $rates['service_agreement_number'] ?: 'legacy-'.$machine->id;
                    $remainingByAgreement[$agreementKey] ??= [
                        'mono' => (int) $rates['included_mono_pages'],
                        'colour' => (int) $rates['included_colour_pages'],
                    ];
                    $monoUsage = $row['mono_usage'] ?? 0;
                    $colourUsage = $row['colour_usage'] ?? 0;
                    $includedMono = min($remainingByAgreement[$agreementKey]['mono'], $monoUsage);
                    $includedColour = min($remainingByAgreement[$agreementKey]['colour'], $colourUsage);
                    $remainingByAgreement[$agreementKey]['mono'] -= $includedMono;
                    $remainingByAgreement[$agreementKey]['colour'] -= $includedColour;

                    return array_merge($row, $this->pricing->revenueForChargeableUsage($machine, $monoUsage, $colourUsage, $rates, $includedMono, $includedColour), [
                        'machine_name' => $machine->machine_name ?? $machine->serial_number,
                        'site_id' => $machine->site_id,
                        'site_name' => $machine->site?->name,
                        'client_id' => $machine->client_id,
                        'client_name' => $machine->client?->name,
                    ]);
                });
            });
    }

    public function revenueSummary(CarbonInterface $from, CarbonInterface $to, ?int $companyId = null): array
    {
        return $this->summariseRevenueRows($this->revenueBetween($from, $to, $companyId));
    }

    public function summariseRevenueRows(Collection $rows): array
    {
        return [
            'rows' => $rows,
            'total_mono_pages' => $rows->sum('mono_usage'),
            'total_colour_pages' => $rows->sum('colour_usage'),
            'total_pages' => $rows->sum('total_usage'),
            'included_mono_pages' => $rows->sum('included_mono_pages'),
            'included_colour_pages' => $rows->sum('included_colour_pages'),
            'included_total_pages' => $rows->sum('included_total_pages'),
            'chargeable_mono_pages' => $rows->sum('chargeable_mono_pages'),
            'chargeable_colour_pages' => $rows->sum('chargeable_colour_pages'),
            'chargeable_total_pages' => $rows->sum('chargeable_total_pages'),
            'mono_revenue' => round($rows->sum('mono_revenue'), 2),
            'colour_revenue' => round($rows->sum('colour_revenue'), 2),
            'total_revenue' => round($rows->sum('total_revenue'), 2),
            'by_client' => $this->groupRevenue($rows, 'client_id', 'client_name'),
            'by_site' => $this->groupRevenue($rows, 'site_id', 'site_name'),
            'by_machine' => $this->groupRevenue($rows, 'machine_id', 'machine_name'),
        ];
    }

    private function readingsFor($query, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $query->whereBetween('reading_date', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('reading_date')
            ->get();
    }

    private function usageRow(MeterReading $reading, ?int $total, ?int $mono, ?int $colour, bool $unknown, bool $reset): array
    {
        return [
            'date' => $reading->reading_date->toDateString(),
            'machine_id' => $reading->machine_id,
            'total_usage' => $total,
            'mono_usage' => $mono,
            'colour_usage' => $colour,
            'usage_unknown' => $unknown,
            'counter_reset_detected' => $reset,
        ];
    }

    private function counterDiff(?int $current, ?int $previous): ?int
    {
        if ($current === null || $previous === null || $current < $previous) {
            return null;
        }

        return $current - $previous;
    }

    private function summariseRows(string $date, Collection $rows): array
    {
        return [
            'date' => $date,
            'total_usage' => $rows->sum('total_usage'),
            'mono_usage' => $rows->sum('mono_usage'),
            'colour_usage' => $rows->sum('colour_usage'),
            'usage_unknown' => $rows->contains('usage_unknown', true),
            'counter_reset_detected' => $rows->contains('counter_reset_detected', true),
        ];
    }

    private function groupRevenue(Collection $rows, string $key, string $label): Collection
    {
        return $rows->groupBy($key)
            ->map(fn (Collection $group) => [
                'id' => $group->first()[$key],
                'name' => $group->first()[$label] ?? 'Unknown',
                'mono_pages' => $group->sum('mono_usage'),
                'colour_pages' => $group->sum('colour_usage'),
                'total_pages' => $group->sum('total_usage'),
                'included_pages' => $group->sum('included_total_pages'),
                'chargeable_pages' => $group->sum('chargeable_total_pages'),
                'revenue' => round($group->sum('total_revenue'), 2),
            ])
            ->sortByDesc('revenue')
            ->values();
    }
}
