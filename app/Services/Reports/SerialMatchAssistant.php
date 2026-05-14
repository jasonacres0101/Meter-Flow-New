<?php

namespace App\Services\Reports;

use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Support\Tenant;
use Illuminate\Support\Collection;

class SerialMatchAssistant
{
    public function __construct(private readonly ReportTemplateSuggestionService $suggestions) {}

    /**
     * @return Collection<int, array{candidate: string, normalised: string, machine: Machine|null, confidence: int, reason: string, is_exact: bool}>
     */
    public function suggestionsFor(IncomingReportEmail $email): Collection
    {
        $machines = Machine::query()
            ->with(['client.company', 'site', 'machineModel'])
            ->when($email->company_id, fn ($query) => $query->whereHas('client', fn ($query) => $query->where('company_id', $email->company_id)))
            ->get()
            ->mapWithKeys(fn (Machine $machine) => [$this->normalise($machine->serial_number) => $machine]);

        return $this->candidates($email->body_text)
            ->map(function (array $candidate) use ($machines) {
                $normalised = $this->normalise($candidate['value']);
                $machine = $machines->get($normalised);

                return [
                    'candidate' => $candidate['value'],
                    'normalised' => $normalised,
                    'machine' => $machine,
                    'confidence' => $machine ? $candidate['confidence'] : min(45, $candidate['confidence']),
                    'reason' => $machine
                        ? $candidate['reason'].' Exact existing machine serial match.'
                        : $candidate['reason'].' No existing machine has this serial.',
                    'is_exact' => (bool) $machine,
                ];
            })
            ->sortByDesc(fn (array $suggestion) => [$suggestion['is_exact'], $suggestion['confidence']])
            ->values();
    }

    public function normalise(string $serial): string
    {
        return str($serial)->upper()->replaceMatches('/[^A-Z0-9]+/', '')->toString();
    }

    /**
     * @return Collection<int, array{value: string, confidence: int, reason: string}>
     */
    private function candidates(string $body): Collection
    {
        $fieldCandidates = $this->suggestions->detectedFields($body)
            ->filter(fn (array $field) => str($field['label'])->lower()->contains(['serial', 'device id', 'machine id', 'asset id', 'identifier']))
            ->map(fn (array $field) => [
                'value' => $this->cleanCandidate((string) $field['value']),
                'confidence' => 90,
                'reason' => "Detected from labelled field '{$field['label']}'.",
            ]);

        preg_match_all('/(?:serial|s\/n|device id|machine id|asset id|identifier|sn)[^\r\nA-Z0-9]{0,8}([A-Z0-9][A-Z0-9\-\s]{5,40})/i', $body, $matches);
        $patternCandidates = collect($matches[1] ?? [])
            ->map(fn (string $value) => [
                'value' => $this->cleanCandidate($value),
                'confidence' => 70,
                'reason' => 'Detected from nearby serial wording.',
            ]);

        return $fieldCandidates
            ->merge($patternCandidates)
            ->filter(fn (array $candidate) => $this->looksLikeSerial($candidate['value']))
            ->unique(fn (array $candidate) => $this->normalise($candidate['value']))
            ->values();
    }

    private function cleanCandidate(string $value): string
    {
        return str($value)
            ->before(',')
            ->before('|')
            ->trim()
            ->replaceMatches('/^(serial|s\/n|sn|device id|machine id|asset id|identifier)\s*[:#-]?\s*/i', '')
            ->trim()
            ->toString();
    }

    private function looksLikeSerial(string $value): bool
    {
        $normalised = $this->normalise($value);

        return strlen($normalised) >= 6
            && strlen($normalised) <= 40
            && preg_match('/\d/', $normalised)
            && preg_match('/[A-Z]/', $normalised);
    }
}
