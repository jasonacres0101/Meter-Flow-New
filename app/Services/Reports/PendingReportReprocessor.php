<?php

namespace App\Services\Reports;

use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\ReportTemplate;
use Illuminate\Support\Collection;
use Throwable;

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
            ->each(fn (IncomingReportEmail $email) => $this->processQuietly($email));
    }

    /**
     * @return Collection<int, IncomingReportEmail>
     */
    public function forTemplate(ReportTemplate $template): Collection
    {
        $template->loadMissing('machineModel');
        $machineIds = Machine::query()
            ->where(function ($query) use ($template) {
                $query->where('machine_model_id', $template->machine_model_id);

                if ($template->company_id === null && $template->machineModel) {
                    $query->orWhere(function ($query) use ($template) {
                        $query->where('manufacturer', $template->machineModel->manufacturer)
                            ->where('model', $template->machineModel->model_name);
                    });
                }
            })
            ->when($template->company_id, fn ($query) => $query->whereHas('client', fn ($query) => $query->where('company_id', $template->company_id)))
            ->pluck('id');

        if ($machineIds->isEmpty()) {
            return collect();
        }

        $emails = IncomingReportEmail::query()
            ->whereIn('machine_id', $machineIds)
            ->whereIn('parse_status', [
                IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                IncomingReportEmail::STATUS_FAILED,
            ])
            ->when($template->company_id, fn ($query) => $query->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $template->company_id)))
            ->latest('received_at')
            ->get();

        $emails->each(fn (IncomingReportEmail $email) => $this->processQuietly($email));

        return $emails
            ->map(fn (IncomingReportEmail $email) => $email->fresh())
            ->filter(fn (IncomingReportEmail $email) => $email->parse_status === IncomingReportEmail::STATUS_PARSED)
            ->values();
    }

    private function processQuietly(IncomingReportEmail $email): void
    {
        try {
            $this->processor->process($email);
        } catch (Throwable) {
            // The processor records the failure on the email; keep reprocessing the rest.
        }
    }
}
