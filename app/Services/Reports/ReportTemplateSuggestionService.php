<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

class ReportTemplateSuggestionService
{
    public function detectedFields(string $body): Collection
    {
        preg_match_all('/^[^\S\r\n]*(?:\[([^\]\r\n]{2,80})\]|([^:,\r\n=\|]{2,80}))[^\S\r\n]*[:,=\|][^\S\r\n]*([^\r\n]+)[^\S\n]*$/m', $body, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn (array $match) => ['label' => trim($match[1] ?: $match[2]), 'value' => trim($match[3])])
            ->filter(fn (array $row) => filled($row['label']) && filled($row['value']))
            ->values();
    }

    public function suggestParserConfiguration(string $body): array
    {
        $fields = $this->detectedFields($body);

        return collect([
            'serial_number_labels' => $this->labelsLike($fields, ['serial number', 'serial no', 'serial']),
            'report_date_labels' => $this->labelsLike($fields, ['date', 'timestamp', 'report date', 'send date']),
            'machine_name_labels' => $this->labelsLike($fields, ['machine name', 'device name', 'asset name']),
            'model_name_labels' => $this->labelsLike($fields, ['device model', 'model name', 'model', 'device type']),
            'total_counter_labels' => $this->labelsLike($fields, ['total counter', 'total count', 'total pages', 'total impressions']),
            'mono_counter_labels' => $this->labelsLike($fields, ['black & white total print count', 'b/w total', 'mono total', 'black impressions', 'total black counter']),
            'colour_counter_labels' => $this->labelsLike($fields, ['colour total print count', 'color total print count', 'colour impressions', 'color impressions', 'total colour counter', 'total color counter']),
            'copy_mono_counter_labels' => $this->labelsLike($fields, ['black & white copy count', 'copy mono', 'copy b/w', 'black copy impressions']),
            'copy_colour_counter_labels' => $this->labelsLike($fields, ['full colour copy count', 'full color copy count', 'copy colour', 'copy color', 'colour copy impressions']),
            'print_mono_counter_labels' => $this->labelsLike($fields, ['black & white print count', 'print mono', 'print b/w', 'black print impressions']),
            'print_colour_counter_labels' => $this->labelsLike($fields, ['full colour print count', 'full color print count', 'print colour', 'print color', 'colour print impressions']),
            'scan_counter_labels' => $this->labelsLike($fields, ['scanner count', 'scan count', 'scan images', 'scan counter', 'scan/fax counter', 'total scan/fax counter']),
            'black_toner_percentage_labels' => $this->labelsLike($fields, ['black toner', 'toner residual (bk)']),
            'cyan_toner_percentage_labels' => $this->labelsLike($fields, ['cyan toner', 'toner residual (c)']),
            'magenta_toner_percentage_labels' => $this->labelsLike($fields, ['magenta toner', 'toner residual (m)']),
            'yellow_toner_percentage_labels' => $this->labelsLike($fields, ['yellow toner', 'toner residual (y)']),
            'black_inserted_toner_number_labels' => $this->labelsLike($fields, ['inserted toner number (bk)', 'black inserted toner number']),
            'cyan_inserted_toner_number_labels' => $this->labelsLike($fields, ['inserted toner number (c)', 'cyan inserted toner number']),
            'magenta_inserted_toner_number_labels' => $this->labelsLike($fields, ['inserted toner number (m)', 'magenta inserted toner number']),
            'yellow_inserted_toner_number_labels' => $this->labelsLike($fields, ['inserted toner number (y)', 'yellow inserted toner number']),
            'waste_toner_status_labels' => $this->labelsLike($fields, ['waste toner']),
            'current_status_labels' => $this->labelsLike($fields, ['current status', 'device state']),
            'service_status_labels' => $this->labelsLike($fields, ['service status', 'service required']),
        ])
            ->filter(fn (array $labels) => $labels !== [])
            ->all();
    }

    public function suggestParserType(string $body): string
    {
        $lower = str($body)->lower()->toString();

        return str_contains($lower, 'mx-') || str_contains($lower, 'toner residual')
            ? 'sharp_mx_status_email'
            : 'generic_counter_email';
    }

    public function labelsLike(Collection $fields, array $needles): array
    {
        return $fields
            ->pluck('label')
            ->filter(function (string $label) use ($needles) {
                $lower = str($label)->lower()->toString();
                $normalisedLabel = str($label)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();

                return collect($needles)->contains(function (string $needle) use ($lower, $normalisedLabel) {
                    $needle = str($needle)->lower()->toString();
                    $normalisedNeedle = str($needle)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();

                    if (str_contains($lower, $needle)) {
                        return true;
                    }

                    return filled($normalisedNeedle) && str_contains($normalisedLabel, $normalisedNeedle);
                });
            })
            ->unique()
            ->values()
            ->all();
    }
}
