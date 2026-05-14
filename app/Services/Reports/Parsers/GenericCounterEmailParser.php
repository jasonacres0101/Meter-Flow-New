<?php

namespace App\Services\Reports\Parsers;

use App\Models\IncomingReportEmail;
use App\Models\ReportTemplate;
use App\Services\Reports\ParsedMachineReport;
use Carbon\CarbonImmutable;
use Throwable;

class GenericCounterEmailParser implements MachineReportParser
{
    public function parse(IncomingReportEmail $email, ?ReportTemplate $template = null): ParsedMachineReport
    {
        $body = $email->body_text;
        $config = $template?->parser_configuration ?? [];

        $reportedAt = $this->dateValue($body, $this->labels($config, 'report_date_labels', ['Date', 'Timestamp', 'Report Date']))
            ?? CarbonImmutable::parse($email->received_at);
        $insertedTonerNumbers = collect([
            'black' => $this->intValue($body, $this->labels($config, 'black_inserted_toner_number_labels', ['Inserted Toner Number (Bk)', 'Black Inserted Toner Number'])),
            'cyan' => $this->intValue($body, $this->labels($config, 'cyan_inserted_toner_number_labels', ['Inserted Toner Number (C)', 'Cyan Inserted Toner Number'])),
            'magenta' => $this->intValue($body, $this->labels($config, 'magenta_inserted_toner_number_labels', ['Inserted Toner Number (M)', 'Magenta Inserted Toner Number'])),
            'yellow' => $this->intValue($body, $this->labels($config, 'yellow_inserted_toner_number_labels', ['Inserted Toner Number (Y)', 'Yellow Inserted Toner Number'])),
        ])->filter(fn (?int $value) => $value !== null)->all();
        $raw = ['parser' => 'generic_counter_email', 'template_id' => $template?->id];

        if ($insertedTonerNumbers !== []) {
            $raw['toner_lifecycle'] = ['inserted_toner_number' => $insertedTonerNumbers];
        }

        return new ParsedMachineReport(
            machineName: $this->value($body, $this->labels($config, 'machine_name_labels', ['Machine Name', 'Device Name', 'Asset Name'])),
            modelName: $this->value($body, $this->labels($config, 'model_name_labels', ['Model Name', 'Device Model', 'Device Type'])),
            serialNumber: $this->value($body, $this->labels($config, 'serial_number_labels', ['Serial Number', 'Serial No', 'Serial'])),
            reportedAt: $reportedAt,
            currentStatus: $this->value($body, $this->labels($config, 'current_status_labels', ['Current Status', 'Device State', 'Status'])),
            totalCounter: $this->intValue($body, $this->labels($config, 'total_counter_labels', ['Total Counter', 'Total Count', 'Total Pages', 'Total Impressions'])),
            monoCounter: $this->intValue($body, $this->labels($config, 'mono_counter_labels', ['Mono Count', 'B/W Count', 'Black Impressions', 'Black & White Total Print Count'])),
            colourCounter: $this->intValue($body, $this->labels($config, 'colour_counter_labels', ['Colour Count', 'Color Count', 'Color Impressions', 'Colour Impressions', 'Colour Total Print Count'])),
            copyMonoCounter: $this->intValue($body, $this->labels($config, 'copy_mono_counter_labels', ['Copy B/W', 'Copy Mono', 'Black Copy Impressions', 'Black & White Copy Count'])),
            copyColourCounter: $this->intValue($body, $this->labels($config, 'copy_colour_counter_labels', ['Copy Colour', 'Copy Color', 'Color Copy Impressions', 'Colour Copy Impressions', 'Full Colour Copy Count'])),
            printMonoCounter: $this->intValue($body, $this->labels($config, 'print_mono_counter_labels', ['Print B/W', 'Print Mono', 'Black Print Impressions', 'Black & White Print Count'])),
            printColourCounter: $this->intValue($body, $this->labels($config, 'print_colour_counter_labels', ['Print Colour', 'Print Color', 'Color Print Impressions', 'Colour Print Impressions', 'Full Colour Print Count'])),
            scanCounter: $this->intValue($body, $this->labels($config, 'scan_counter_labels', ['Scan Count', 'Scan Images', 'Scanner Counter'])),
            faxSentCounter: $this->intValue($body, $this->labels($config, 'fax_sent_counter_labels', ['Fax Sent', 'FAX Send'])),
            faxReceivedCounter: $this->intValue($body, $this->labels($config, 'fax_received_counter_labels', ['Fax Received', 'FAX Receive'])),
            toners: [
                'black' => $this->percentageValue($body, $this->labels($config, 'black_toner_percentage_labels', ['Black Toner', 'K Toner'])),
                'cyan' => $this->percentageValue($body, $this->labels($config, 'cyan_toner_percentage_labels', ['Cyan Toner', 'C Toner'])),
                'magenta' => $this->percentageValue($body, $this->labels($config, 'magenta_toner_percentage_labels', ['Magenta Toner', 'M Toner'])),
                'yellow' => $this->percentageValue($body, $this->labels($config, 'yellow_toner_percentage_labels', ['Yellow Toner', 'Y Toner'])),
            ],
            wasteTonerStatus: $this->value($body, $this->labels($config, 'waste_toner_status_labels', ['Waste Toner', 'Waste Toner Container'])),
            paperTrayStatus: [],
            serviceStatus: $this->value($body, $this->labels($config, 'service_status_labels', ['Service Status', 'Service Required'])),
            raw: $raw,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $fallbacks
     * @return array<int, string>
     */
    private function labels(array $config, string $key, array $fallbacks): array
    {
        return collect($config[$key] ?? [])
            ->merge($fallbacks)
            ->filter()
            ->unique(fn (string $label) => mb_strtolower($label))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function value(string $body, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/^\s*'.preg_quote($label, '/').'\s*[:=\|]\s*(.+?)\s*$/mi';

            if (preg_match($pattern, $body, $matches) && filled(trim($matches[1]))) {
                return $this->cleanValue($matches[1]);
            }
        }

        return null;
    }

    private function cleanValue(string $value): string
    {
        return trim(preg_replace('/^\|+/', '', trim($value)));
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function intValue(string $body, array $labels): ?int
    {
        $value = $this->value($body, $labels);

        return preg_match('/[0-9][0-9,]*/', (string) $value, $matches)
            ? (int) str_replace(',', '', $matches[0])
            : null;
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function percentageValue(string $body, array $labels): ?int
    {
        $value = $this->value($body, $labels);

        return preg_match('/\d+/', (string) $value, $matches)
            ? (int) $matches[0]
            : null;
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function dateValue(string $body, array $labels): ?CarbonImmutable
    {
        $value = $this->value($body, $labels);

        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
