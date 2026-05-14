<?php

namespace App\Console\Commands;

use App\Services\SaasBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateBillingInvoices extends Command
{
    protected $signature = 'billing:generate-invoices {--date=} {--force}';

    protected $description = 'Generate SaaS account billing invoices at month end.';

    public function handle(SaasBillingService $billing): int
    {
        $date = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : CarbonImmutable::now();

        if (! $this->option('force') && ! $date->isLastOfMonth()) {
            $this->info('Skipped. Billing invoices are generated on the last day of the month.');

            return self::SUCCESS;
        }

        $invoices = $billing->generateMonthEndInvoices($date);

        $this->info("Generated {$invoices->count()} billing invoices.");

        return self::SUCCESS;
    }
}
