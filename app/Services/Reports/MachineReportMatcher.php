<?php

namespace App\Services\Reports;

use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Repositories\MachineRepository;

class MachineReportMatcher
{
    public function __construct(private readonly MachineRepository $machines) {}

    public function extractSerialNumber(string $body): ?string
    {
        return preg_match('/(?:\[serial(?:\s+number| no\.?)?\]|serial(?:\s+number| no\.?)?)\s*[:,=|]\s*([A-Z0-9-]+)/i', $body, $matches)
            ? trim($matches[1])
            : null;
    }

    public function match(IncomingReportEmail $email): ?Machine
    {
        $machine = $this->machines->findBySerialNumber($this->extractSerialNumber($email->body_text));

        if (! $machine) {
            $email->forceFill([
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => 'No machine matched the serial number in the report email.',
            ])->save();

            return null;
        }

        $email->forceFill([
            'machine_id' => $machine->id,
            'company_id' => $machine->client->company_id,
            'parse_status' => IncomingReportEmail::STATUS_PENDING,
            'parse_error' => null,
        ])->save();

        return $machine;
    }
}
