<?php

namespace App\Services\Reports;

use Carbon\CarbonInterface;

readonly class ParsedMachineReport
{
    public function __construct(
        public ?string $machineName,
        public ?string $modelName,
        public ?string $serialNumber,
        public CarbonInterface $reportedAt,
        public ?string $currentStatus,
        public ?int $totalCounter,
        public ?int $monoCounter,
        public ?int $colourCounter,
        public ?int $copyMonoCounter,
        public ?int $copyColourCounter,
        public ?int $printMonoCounter,
        public ?int $printColourCounter,
        public ?int $scanCounter,
        public ?int $faxSentCounter,
        public ?int $faxReceivedCounter,
        public array $toners = [],
        public ?string $wasteTonerStatus = null,
        public array $paperTrayStatus = [],
        public ?string $serviceStatus = null,
        public array $raw = [],
    ) {}

    public function toArray(): array
    {
        return [
            'machine_name' => $this->machineName,
            'model_name' => $this->modelName,
            'serial_number' => $this->serialNumber,
            'reported_at' => $this->reportedAt->toIso8601String(),
            'current_status' => $this->currentStatus,
            'total_counter' => $this->totalCounter,
            'mono_counter' => $this->monoCounter,
            'colour_counter' => $this->colourCounter,
            'copy_mono_counter' => $this->copyMonoCounter,
            'copy_colour_counter' => $this->copyColourCounter,
            'print_mono_counter' => $this->printMonoCounter,
            'print_colour_counter' => $this->printColourCounter,
            'scan_counter' => $this->scanCounter,
            'fax_sent_counter' => $this->faxSentCounter,
            'fax_received_counter' => $this->faxReceivedCounter,
            'toners' => $this->toners,
            'waste_toner_status' => $this->wasteTonerStatus,
            'paper_tray_status' => $this->paperTrayStatus,
            'service_status' => $this->serviceStatus,
            'raw' => $this->raw,
        ];
    }
}
