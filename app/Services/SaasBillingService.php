<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\BillingSnapshot;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SaasBillingService
{
    /**
     * @return Collection<int, BillingSnapshot>
     */
    public function captureMonthlyMachineSnapshots(?CarbonImmutable $date = null): Collection
    {
        $date ??= CarbonImmutable::now();
        $setting = BillingSetting::current();
        [$periodStart, $periodEnd] = $this->periodFor($date);

        return Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Company $company) use ($setting, $periodStart, $periodEnd, $date): BillingSnapshot {
                $machineCount = $company->machines()
                    ->where('machines.is_active', true)
                    ->count();

                $monthlyMachineRate = $company->monthly_machine_rate_override ?? $setting->monthly_machine_rate;

                return BillingSnapshot::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ],
                    [
                        'snapshot_date' => $date->toDateString(),
                        'active_machine_count' => $machineCount,
                        'monthly_machine_rate' => $monthlyMachineRate,
                        'currency' => $setting->currency,
                    ],
                );
            });
    }

    /**
     * @return Collection<int, BillingInvoice>
     */
    public function generateMonthEndInvoices(?CarbonImmutable $date = null): Collection
    {
        $date ??= CarbonImmutable::now();
        $setting = BillingSetting::current();
        [$periodStart, $periodEnd] = $this->periodFor($date);

        $snapshots = BillingSnapshot::query()
            ->with('company')
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->get();

        if ($snapshots->isEmpty()) {
            $snapshots = $this->captureMonthlyMachineSnapshots($date);
        }

        return $snapshots->map(function (BillingSnapshot $snapshot) use ($date, $setting): BillingInvoice {
            $subtotal = round($snapshot->active_machine_count * (float) $snapshot->monthly_machine_rate, 2);

            return BillingInvoice::query()->updateOrCreate(
                [
                    'company_id' => $snapshot->company_id,
                    'period_start' => $snapshot->period_start,
                    'period_end' => $snapshot->period_end,
                ],
                [
                    'billing_snapshot_id' => $snapshot->id,
                    'invoice_number' => $this->invoiceNumber($snapshot),
                    'invoice_date' => $date->toDateString(),
                    'due_date' => $date->addDays($setting->payment_terms_days)->toDateString(),
                    'active_machine_count' => $snapshot->active_machine_count,
                    'monthly_machine_rate' => $snapshot->monthly_machine_rate,
                    'subtotal' => $subtotal,
                    'tax_total' => 0,
                    'total' => $subtotal,
                    'currency' => $snapshot->currency,
                    'status' => BillingInvoice::STATUS_ISSUED,
                ],
            );
        });
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodFor(CarbonImmutable $date): array
    {
        return [$date->startOfMonth(), $date->endOfMonth()];
    }

    private function invoiceNumber(BillingSnapshot $snapshot): string
    {
        return sprintf('INV-%s-%04d', $snapshot->period_end->format('Ym'), $snapshot->company_id);
    }
}
