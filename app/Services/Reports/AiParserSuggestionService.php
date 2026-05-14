<?php

namespace App\Services\Reports;

use App\Models\PlatformAiSetting;
use App\Support\ParserRegistry;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiParserSuggestionService
{
    /**
     * @return array{parser_type: string, parser_configuration: array<string, array<int, string>>, explanation: string|null}
     *
     * @throws RequestException
     */
    public function suggest(string $body, array $detectedConfiguration = []): array
    {
        $settings = $this->settings();

        $response = Http::withToken($settings['api_key'])
            ->acceptJson()
            ->timeout($settings['timeout'])
            ->post(rtrim($settings['base_url'], '/').'/responses', [
                'model' => $settings['model'],
                'instructions' => $this->instructions(),
                'input' => $this->input($body, $detectedConfiguration),
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'parser_template_suggestion',
                        'schema' => $this->schema(),
                        'strict' => true,
                    ],
                ],
            ])
            ->throw()
            ->json();

        return $this->normalise($this->extractJson($response), $detectedConfiguration);
    }

    /**
     * @return array{api_key: string, model: string, base_url: string, timeout: int}
     */
    public function settings(): array
    {
        $saved = PlatformAiSetting::current();

        $apiKey = $saved?->isReady() ? $saved->api_key : config('services.openai.key');
        $model = $saved?->isReady() ? $saved->model : config('services.openai.model', 'gpt-4.1-mini');
        $baseUrl = $saved?->isReady() ? $saved->base_url : config('services.openai.base_url', 'https://api.openai.com/v1');
        $timeout = $saved?->isReady() ? $saved->timeout : (int) config('services.openai.timeout', 30);

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        return [
            'api_key' => $apiKey,
            'model' => $model,
            'base_url' => $baseUrl,
            'timeout' => max(5, min(120, (int) $timeout)),
        ];
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
You help a copier monitoring SaaS map machine status emails into parser template JSON.
Return JSON only. Choose parser_type from the allowed parser types.
Use exact labels from the email body. Do not invent labels.
Prefer generic_counter_email for unknown or pipe/comma/table formats.
Use sharp_mx_status_email only when the report is clearly a Sharp MX status/counter email.
Parser configuration values must be arrays of labels from the email.
Return every allowed parser configuration key. Use an empty array for keys that do not apply.
PROMPT;
    }

    private function input(string $body, array $detectedConfiguration): string
    {
        return json_encode([
            'allowed_parser_types' => ParserRegistry::keys(),
            'allowed_configuration_keys' => $this->configurationKeys(),
            'local_suggestion' => $detectedConfiguration,
            'email_body' => str($body)->limit(12000, '')->toString(),
        ], JSON_PRETTY_PRINT);
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['parser_type', 'parser_configuration', 'explanation'],
            'properties' => [
                'parser_type' => [
                    'type' => 'string',
                    'enum' => ParserRegistry::keys(),
                ],
                'parser_configuration' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => $this->configurationKeys(),
                    'properties' => collect($this->configurationKeys())
                        ->mapWithKeys(fn (string $key) => [$key => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ]])
                        ->all(),
                ],
                'explanation' => [
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function configurationKeys(): array
    {
        return [
            'serial_number_labels',
            'report_date_labels',
            'machine_name_labels',
            'model_name_labels',
            'current_status_labels',
            'total_counter_labels',
            'mono_counter_labels',
            'colour_counter_labels',
            'copy_mono_counter_labels',
            'copy_colour_counter_labels',
            'print_mono_counter_labels',
            'print_colour_counter_labels',
            'scan_counter_labels',
            'fax_sent_counter_labels',
            'fax_received_counter_labels',
            'black_toner_percentage_labels',
            'cyan_toner_percentage_labels',
            'magenta_toner_percentage_labels',
            'yellow_toner_percentage_labels',
            'black_inserted_toner_number_labels',
            'cyan_inserted_toner_number_labels',
            'magenta_inserted_toner_number_labels',
            'yellow_inserted_toner_number_labels',
            'waste_toner_status_labels',
            'service_status_labels',
        ];
    }

    private function extractJson(array $response): array
    {
        $text = $response['output_text'] ?? null;

        if (! $text && isset($response['output'])) {
            foreach ($response['output'] as $item) {
                foreach ($item['content'] ?? [] as $content) {
                    if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                        $text = $content['text'];
                        break 2;
                    }
                }
            }
        }

        $decoded = json_decode((string) $text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned an invalid parser suggestion.');
        }

        return $decoded;
    }

    private function normalise(array $suggestion, array $detectedConfiguration): array
    {
        $parserType = in_array($suggestion['parser_type'] ?? null, ParserRegistry::keys(), true)
            ? $suggestion['parser_type']
            : 'generic_counter_email';

        $configuration = collect($suggestion['parser_configuration'] ?? [])
            ->filter(fn ($labels, string $key) => str_ends_with($key, '_labels') && is_array($labels))
            ->map(fn (array $labels) => collect($labels)
                ->filter(fn ($label) => is_string($label) && filled($label))
                ->map(fn (string $label) => trim($label))
                ->unique()
                ->values()
                ->all())
            ->filter(fn (array $labels) => $labels !== [])
            ->all();

        if ($configuration === []) {
            $configuration = $detectedConfiguration;
        }

        return [
            'parser_type' => $parserType,
            'parser_configuration' => $configuration,
            'explanation' => is_string($suggestion['explanation'] ?? null) ? $suggestion['explanation'] : null,
        ];
    }
}
