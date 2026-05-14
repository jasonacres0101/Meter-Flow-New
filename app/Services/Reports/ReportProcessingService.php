<?php

namespace App\Services\Reports;

use App\Models\ConsumableReading;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MeterReading;
use App\Models\ReportTemplate;
use App\Services\TonerAlertService;
use Throwable;

class ReportProcessingService
{
    public function __construct(
        private readonly MachineReportMatcher $matcher,
        private readonly ParserFactory $parsers,
        private readonly TonerAlertService $tonerAlerts,
    ) {}

    public function process(IncomingReportEmail $email): ?MeterReading
    {
        $machine = $email->machine ?: $this->matcher->match($email);

        if (! $machine) {
            return null;
        }

        $template = $this->activeTemplate($machine, $email);

        if (! $template) {
            $email->forceFill([
                'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                'parse_error' => 'Matched machine by serial number, but no active report template exists for this manufacturer and model.',
            ])->save();

            return null;
        }

        try {
            $parserType = $template->parser_type ?: $machine->machineModel->parser_type;
            $parsed = $this->parsers->make($parserType)->parse($email, $template);
            $reading = $this->storeMeterReading($machine, $email, $parsed);
            $this->storeConsumableReadings($machine, $email, $parsed);

            $email->forceFill([
                'parsed_payload' => $parsed->toArray(),
                'parse_status' => IncomingReportEmail::STATUS_PARSED,
                'parse_error' => null,
            ])->save();

            return $reading;
        } catch (Throwable $exception) {
            $email->forceFill([
                'parse_status' => IncomingReportEmail::STATUS_FAILED,
                'parse_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    private function activeTemplate(Machine $machine, IncomingReportEmail $email): ?ReportTemplate
    {
        return ReportTemplate::query()
            ->where(function ($query) use ($machine) {
                $query->where('machine_model_id', $machine->machine_model_id)
                    ->orWhereHas('machineModel', function ($query) use ($machine) {
                        $query->whereNull('company_id')
                            ->where('manufacturer', $machine->manufacturer)
                            ->where('model_name', $machine->model);
                    });
            })
            ->where('is_active', true)
            ->where(function ($query) use ($machine, $email) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $email->company_id)
                    ->orWhere('company_id', $machine->client->company_id);
            })
            ->orderByRaw('case when company_id = ? then 0 when company_id is null then 1 else 2 end', [$machine->client->company_id])
            ->orderByRaw('case when machine_model_id = ? then 0 else 1 end', [$machine->machine_model_id])
            ->latest()
            ->first();
    }

    private function storeMeterReading(Machine $machine, IncomingReportEmail $email, ParsedMachineReport $parsed): MeterReading
    {
        $previous = $machine->meterReadings()
            ->where('reading_date', '<', $parsed->reportedAt)
            ->orderByDesc('reading_date')
            ->first();

        return MeterReading::updateOrCreate(
            [
                'machine_id' => $machine->id,
                'company_id' => $machine->client->company_id,
                'reading_date' => $parsed->reportedAt,
            ],
            [
                'incoming_report_email_id' => $email->id,
                'total_counter' => $parsed->totalCounter,
                'mono_counter' => $parsed->monoCounter,
                'colour_counter' => $parsed->colourCounter,
                'copy_mono_counter' => $parsed->copyMonoCounter,
                'copy_colour_counter' => $parsed->copyColourCounter,
                'print_mono_counter' => $parsed->printMonoCounter,
                'print_colour_counter' => $parsed->printColourCounter,
                'scan_counter' => $parsed->scanCounter,
                'fax_sent_counter' => $parsed->faxSentCounter,
                'fax_received_counter' => $parsed->faxReceivedCounter,
                'current_status' => $parsed->currentStatus,
                'paper_tray_status' => $parsed->paperTrayStatus,
                'service_status' => $parsed->serviceStatus,
                'usage_unknown' => ! $previous,
                'counter_reset_detected' => $previous && $parsed->totalCounter !== null && $previous->total_counter !== null && $parsed->totalCounter < $previous->total_counter,
            ],
        );
    }

    private function storeConsumableReadings(Machine $machine, IncomingReportEmail $email, ParsedMachineReport $parsed): void
    {
        foreach ($parsed->toners as $colour => $percentage) {
            if ($percentage === null) {
                continue;
            }

            ConsumableReading::firstOrCreate(
                [
                    'machine_id' => $machine->id,
                    'company_id' => $machine->client->company_id,
                    'consumable_type' => 'toner',
                    'colour' => $colour,
                    'reading_date' => $parsed->reportedAt,
                ],
                [
                    'incoming_report_email_id' => $email->id,
                    'percentage' => $percentage,
                    'status' => $this->tonerAlerts->statusFor($machine->client->company_id, $percentage, $colour),
                ],
            );
        }

        if ($parsed->wasteTonerStatus) {
            ConsumableReading::firstOrCreate(
                [
                    'machine_id' => $machine->id,
                    'company_id' => $machine->client->company_id,
                    'consumable_type' => 'waste_toner',
                    'colour' => null,
                    'reading_date' => $parsed->reportedAt,
                ],
                [
                    'incoming_report_email_id' => $email->id,
                    'status' => $parsed->wasteTonerStatus,
                ],
            );
        }
    }
}
