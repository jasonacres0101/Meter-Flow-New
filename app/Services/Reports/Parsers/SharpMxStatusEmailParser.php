<?php

namespace App\Services\Reports\Parsers;

use App\Models\IncomingReportEmail;
use App\Models\ReportTemplate;
use App\Services\Reports\ParsedMachineReport;
use Carbon\CarbonImmutable;

class SharpMxStatusEmailParser implements MachineReportParser
{
    public function parse(IncomingReportEmail $email, ?ReportTemplate $template = null): ParsedMachineReport
    {
        $body = $email->body_text;
        $copyMono = $this->intAfterLabel($body, 'Copy Counter\s*[\r\n]+B/W')
            ?? $this->intValue($body, 'Black & White Copy Count');
        $copyColour = $this->intAfterLabel($body, 'Copy Counter\s*[\s\S]*?Color')
            ?? $this->sumNullable(
                $this->intValue($body, 'Two Colour Copy Count'),
                $this->intValue($body, 'Single Colour Copy Count'),
                $this->intValue($body, 'Full Colour Copy Count'),
            );
        $printMono = $this->intAfterLabel($body, 'Printer Counter\s*[\r\n]+B/W')
            ?? $this->intValue($body, 'Black & White Print Count');
        $printColour = $this->intAfterLabel($body, 'Printer Counter\s*[\s\S]*?Color')
            ?? $this->intValue($body, 'Full Colour Print Count');
        $monoCounter = $this->intValue($body, 'Black & White Total Print Count')
            ?? $this->sumNullable($copyMono, $printMono);
        $colourCounter = $this->intValue($body, 'Colour Total Print Count')
            ?? $this->sumNullable($copyColour, $printColour);
        $totalCounter = $this->intAfterHeading($body, 'Total Counter')
            ?? $this->sumNullable($monoCounter, $colourCounter);

        $reportedAt = $this->reportedAt($body, $email);
        $serviceStatus = str_contains(mb_strtolower($body), 'no service is required') ? 'NORMAL' : $this->value($body, 'Maintenance Counter');

        return new ParsedMachineReport(
            machineName: $this->value($body, 'Machine Name'),
            modelName: $this->value($body, 'Model Name') ?? $this->value($body, 'Device Model') ?? $this->value($body, 'Machine Name'),
            serialNumber: $this->value($body, 'Serial Number'),
            reportedAt: $reportedAt,
            currentStatus: $this->value($body, 'Current Status'),
            totalCounter: $totalCounter,
            monoCounter: $monoCounter,
            colourCounter: $colourCounter,
            copyMonoCounter: $copyMono,
            copyColourCounter: $copyColour,
            printMonoCounter: $printMono,
            printColourCounter: $printColour,
            scanCounter: $this->intAfterHeading($body, 'Scanner Counter')
                ?? $this->sumNullable(
                    $this->intValue($body, 'Black & White Scanner Count'),
                    $this->intValue($body, 'Two Colour Scanner Count'),
                    $this->intValue($body, 'Single Colour Scanner Count'),
                    $this->intValue($body, 'Full Colour Scanner Count'),
                ),
            faxSentCounter: $this->intAfterLabel($body, 'FAX Counter\s*[\r\n]+Send'),
            faxReceivedCounter: $this->intAfterLabel($body, 'FAX Counter\s*[\s\S]*?Receive'),
            toners: [
                'black' => $this->percentage($body, 'Black Toner') ?? $this->percentage($body, 'Toner Residual \(Bk\)'),
                'cyan' => $this->percentage($body, 'Cyan Toner') ?? $this->percentage($body, 'Toner Residual \(C\)'),
                'magenta' => $this->percentage($body, 'Magenta Toner') ?? $this->percentage($body, 'Toner Residual \(M\)'),
                'yellow' => $this->percentage($body, 'Yellow Toner') ?? $this->percentage($body, 'Toner Residual \(Y\)'),
            ],
            wasteTonerStatus: $this->value($body, 'Waste Toner'),
            paperTrayStatus: $this->paperTrays($body),
            serviceStatus: $serviceStatus,
            raw: [
                'parser' => 'sharp_mx_status_email',
                'toner_lifecycle' => $this->tonerLifecycle($body),
            ],
        );
    }

    private function value(string $body, string $label): ?string
    {
        return preg_match('/^'.preg_quote($label, '/').'[^\S\r\n]*[:=][^\S\r\n]*(.+?)[^\S\n]*$/mi', $body, $matches) && filled(trim($matches[1]))
            ? trim($matches[1])
            : null;
    }

    private function intAfterHeading(string $body, string $heading): ?int
    {
        return preg_match('/'.preg_quote($heading, '/').'\s*[\r\n]+([0-9,]+)/i', $body, $matches)
            ? (int) str_replace(',', '', $matches[1])
            : null;
    }

    private function intAfterLabel(string $body, string $labelPattern): ?int
    {
        return preg_match('#'.$labelPattern.'\s*[:=]\s*([0-9,]+)#i', $body, $matches)
            ? (int) str_replace(',', '', $matches[1])
            : null;
    }

    private function intValue(string $body, string $label): ?int
    {
        return preg_match('/^'.preg_quote($label, '/').'[^\S\r\n]*[:=][^\S\r\n]*([0-9,]+)[^\S\n]*$/mi', $body, $matches)
            ? (int) str_replace(',', '', $matches[1])
            : null;
    }

    private function percentage(string $body, string $label): ?int
    {
        return preg_match('/^'.$label.'\s*[:=]\s*(\d+)%/mi', $body, $matches)
            ? (int) $matches[1]
            : null;
    }

    private function reportedAt(string $body, IncomingReportEmail $email): CarbonImmutable
    {
        $date = $this->value($body, 'Date')
            ?? $this->firstDateLine($body)
            ?? $email->received_at?->format('Y/m/d H:i:s')
            ?? now()->format('Y/m/d H:i:s');

        $date = trim($date);

        if (preg_match('/^\d{3}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2}$/', $date)) {
            $date = '2'.$date;
        }

        return CarbonImmutable::createFromFormat('Y/m/d H:i:s', $date) ?: CarbonImmutable::parse($date);
    }

    private function firstDateLine(string $body): ?string
    {
        return preg_match('/^\s*(\d{3,4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s*$/m', $body, $matches)
            ? $matches[1]
            : null;
    }

    private function paperTrays(string $body): array
    {
        preg_match_all('/^(Tray \d+|Bypass Tray)\s*:\s*(.+)$/mi', $body, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $match) => [$match[1] => trim($match[2])])->all();
    }

    private function tonerLifecycle(string $body): array
    {
        return [
            'inserted_toner_number' => $this->tonerLifecycleGroup($body, 'Inserted Toner Number'),
            'toner_nn_end' => $this->tonerLifecycleGroup($body, 'Toner NN End'),
            'toner_end' => $this->tonerLifecycleGroup($body, 'Toner End'),
        ];
    }

    private function tonerLifecycleGroup(string $body, string $label): array
    {
        return [
            'black' => $this->intValue($body, $label.' (Bk)'),
            'cyan' => $this->intValue($body, $label.' (C)'),
            'magenta' => $this->intValue($body, $label.' (M)'),
            'yellow' => $this->intValue($body, $label.' (Y)'),
        ];
    }

    private function sumNullable(?int ...$values): ?int
    {
        $present = array_filter($values, fn (?int $value) => $value !== null);

        return $present === [] ? null : array_sum($present);
    }
}
