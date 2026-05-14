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

    /**
     * @return array{should_run_ai: bool, confidence_score: int, confidence_label: string, label: string, tone: string, reason: string}
     */
    public function aiReviewRecommendation(string $body, bool $hasMachineMatch): array
    {
        $fields = $this->detectedFields($body);
        $configuration = $this->suggestParserConfiguration($body);
        $mappedLabels = collect($configuration)->flatten()->count();
        $hasSerial = filled($configuration['serial_number_labels'] ?? []);
        $hasCounters = collect([
            'total_counter_labels',
            'mono_counter_labels',
            'colour_counter_labels',
            'copy_mono_counter_labels',
            'copy_colour_counter_labels',
            'print_mono_counter_labels',
            'print_colour_counter_labels',
            'scan_counter_labels',
        ])->contains(fn (string $key) => filled($configuration[$key] ?? []));
        $hasToners = collect([
            'black_toner_percentage_labels',
            'cyan_toner_percentage_labels',
            'magenta_toner_percentage_labels',
            'yellow_toner_percentage_labels',
            'waste_toner_status_labels',
        ])->contains(fn (string $key) => filled($configuration[$key] ?? []));

        $score = min(90, ($fields->count() * 5) + ($mappedLabels * 8));
        $score += $hasSerial ? 15 : 0;
        $score += $hasCounters ? 15 : 0;
        $score += $hasToners ? 10 : 0;
        $score = max(5, min(95, $score));

        if (! $hasMachineMatch) {
            return [
                'should_run_ai' => true,
                'confidence_score' => min($score, 60),
                'confidence_label' => $this->confidenceLabel(min($score, 60)),
                'label' => 'AI useful',
                'tone' => 'bg-amber-50 text-amber-700',
                'reason' => 'The email is not matched to a machine yet. AI can still draft the mapping, but approve it only after the machine is matched.',
            ];
        }

        if ($score >= 75 && $hasSerial && ($hasCounters || $hasToners)) {
            return [
                'should_run_ai' => false,
                'confidence_score' => $score,
                'confidence_label' => $this->confidenceLabel($score),
                'label' => 'AI optional',
                'tone' => 'bg-emerald-50 text-emerald-700',
                'reason' => 'Local detection found the main fields. Review the draft first; use AI only if the mapping looks incomplete.',
            ];
        }

        if ($score >= 45) {
            return [
                'should_run_ai' => true,
                'confidence_score' => $score,
                'confidence_label' => $this->confidenceLabel($score),
                'label' => 'AI recommended',
                'tone' => 'bg-amber-50 text-amber-700',
                'reason' => 'Some fields were detected, but the mapping may miss counters or toner labels. AI should improve the draft.',
            ];
        }

        return [
            'should_run_ai' => true,
            'confidence_score' => $score,
            'confidence_label' => $this->confidenceLabel($score),
            'label' => 'Run AI',
            'tone' => 'bg-rose-50 text-rose-700',
            'reason' => 'Local detection found very few usable labels. Run AI before approving this template.',
        ];
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

    /**
     * @param  array<string, array<int, string>>  $configuration
     * @return array{mapped_count: int, missing_count: int, unused_count: int, rows: array<int, array{key: string, label: string, value: string|null, status: string, tone: string, note: string}>, unused_fields: array<int, array{label: string, value: string}>}
     */
    public function reviewMapping(string $body, array $configuration): array
    {
        $fields = $this->detectedFields($body);
        $detected = $fields
            ->mapWithKeys(fn (array $field) => [$this->normaliseLabel($field['label']) => $field])
            ->all();
        $used = [];

        $rows = collect($configuration)
            ->filter(fn ($labels, string $key) => str_ends_with($key, '_labels') && is_array($labels))
            ->flatMap(function (array $labels, string $key) use ($detected, &$used) {
                if ($labels === []) {
                    return [[
                        'key' => $key,
                        'label' => '',
                        'value' => null,
                        'status' => 'missing',
                        'tone' => 'bg-slate-50 text-slate-600',
                        'note' => 'No label selected for this parser field.',
                    ]];
                }

                return collect($labels)->map(function (string $label) use ($key, $detected, &$used) {
                    $normalised = $this->normaliseLabel($label);

                    if (isset($detected[$normalised])) {
                        $used[$normalised] = true;

                        return [
                            'key' => $key,
                            'label' => $label,
                            'value' => (string) $detected[$normalised]['value'],
                            'status' => 'matched',
                            'tone' => 'bg-emerald-50 text-emerald-700',
                            'note' => 'Found in the email.',
                        ];
                    }

                    return [
                        'key' => $key,
                        'label' => $label,
                        'value' => null,
                        'status' => 'not found',
                        'tone' => 'bg-rose-50 text-rose-700',
                        'note' => 'AI mapped this label, but it was not found in the detected email fields.',
                    ];
                });
            })
            ->values()
            ->all();

        $unusedFields = $fields
            ->reject(fn (array $field) => isset($used[$this->normaliseLabel($field['label'])]))
            ->values()
            ->all();

        return [
            'mapped_count' => collect($rows)->where('status', 'matched')->count(),
            'missing_count' => collect($rows)->whereIn('status', ['missing', 'not found'])->count(),
            'unused_count' => count($unusedFields),
            'rows' => $rows,
            'unused_fields' => $unusedFields,
        ];
    }

    private function confidenceLabel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'High',
            $score >= 45 => 'Medium',
            default => 'Low',
        };
    }

    private function normaliseLabel(string $label): string
    {
        return str($label)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }
}
