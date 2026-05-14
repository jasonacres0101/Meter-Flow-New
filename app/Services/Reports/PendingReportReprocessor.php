<?php

namespace App\Services\Reports;

use App\Models\IncomingReportEmail;
use App\Models\Machine;
use Illuminate\Support\Collection;

class PendingReportReprocessor
{
    public function __construct(
        private readonly ReportProcessingService $processor,
        private readonly MachineReportMatcher $matcher,
    ) {}

    /**
     * @return Collection<int, IncomingReportEmail>
     */
    public function forMachine(Machine $machine): Collection
    {
        return IncomingReportEmail::query()
            ->whereNull('machine_id')
            ->whereIn('parse_status', [
                IncomingReportEmail::STATUS_PENDING,
                IncomingReportEmail::STATUS_UNMATCHED,
                IncomingReportEmail::STATUS_FAILED,
            ])
            ->latest('received_at')
            ->get()
            ->filter(fn (IncomingReportEmail $email) => strcasecmp((string) $this->matcher->extractSerialNumber($email->body_text), $machine->serial_number) === 0)
            ->values()
            ->each(fn (IncomingReportEmail $email) => $this->processor->process($email));
    }
}
