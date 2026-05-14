<?php

namespace App\Console\Commands;

use App\Models\BillingSetting;
use App\Services\SaasBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CaptureBillingMachineCounts extends Command
{
    protected $signature = 'billing:capture-machine-counts {--date=} {--force}';

    protected $description = 'Capture active machine counts for SaaS account billing on the configured snapshot day.';

    public function handle(SaasBillingService $billing): int
    {
        $date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now();
        $setting = BillingSetting::current();

        if (! $this->option('force') && $date->day !== $setting->snapshot_day) {
            $this->info("Skipped. Billing machine counts are captured on day {$setting->snapshot_day}.");

            return self::SUCCESS;
        }

        $snapshots = $billing->captureMonthlyMachineSnapshots($date);

        $this->info("Captured {$snapshots->count()} billing machine count snapshots.");

        return self::SUCCESS;
    }
}
