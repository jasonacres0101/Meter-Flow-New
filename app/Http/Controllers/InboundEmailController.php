<?php

namespace App\Http\Controllers;

use App\Models\EmailSource;
use App\Services\Reports\IncomingEmailIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InboundEmailController extends Controller
{
    public function __invoke(Request $request, IncomingEmailIngestionService $ingestion): JsonResponse
    {
        $payload = $request->all();
        $source = $this->sourceForRequest($request, $payload);

        $email = $ingestion->store([
            'company_id' => $source?->company_id,
            'from_email' => $payload['from_email'] ?? $payload['from'] ?? $payload['From'] ?? 'unknown@example.invalid',
            'to_email' => $payload['to_email'] ?? $payload['recipient'] ?? $payload['to'] ?? null,
            'subject' => $payload['subject'] ?? $payload['Subject'] ?? null,
            'body_text' => $payload['body_text'] ?? $payload['text'] ?? $payload['stripped-text'] ?? $payload['TextBody'] ?? '',
            'body_html' => $payload['body_html'] ?? $payload['html'] ?? $payload['HtmlBody'] ?? null,
            'received_at' => $payload['received_at'] ?? now()->toIso8601String(),
            'raw_payload' => array_merge($payload, [
                'email_source_id' => $source?->id,
                'provider' => $source?->webhookProvider() ?? $this->routeProvider($request),
            ]),
        ]);

        return response()->json(['id' => $email->id, 'status' => $email->parse_status], 202);
    }

    private function sourceForRequest(Request $request, array $payload): ?EmailSource
    {
        $token = $request->header('X-Email-Source-Token') ?: $request->query('token');

        if (! $token) {
            return null;
        }

        $recipient = $this->emailAddress($payload['to_email'] ?? $payload['recipient'] ?? $payload['to'] ?? null);
        $provider = $this->routeProvider($request);

        $source = EmailSource::query()
            ->where('auth_type', EmailSource::AUTH_WEBHOOK)
            ->where('is_active', true)
            ->get()
            ->first(fn (EmailSource $source) => hash_equals((string) $source->password, (string) $token)
                && (! $recipient || Str::lower($source->mailbox_email) === $recipient)
                && in_array($source->webhookProvider(), [$provider, 'generic'], true));

        abort_unless($source, 403, 'Invalid inbound email source token.');

        $source->forceFill([
            'last_checked_at' => now(),
            'last_success_at' => now(),
            'last_error' => null,
        ])->saveQuietly();

        return $source;
    }

    private function routeProvider(Request $request): string
    {
        return Str::afterLast($request->route()?->getName() ?? 'inbound.generic', '.');
    }

    private function emailAddress(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value, $matches)) {
            return Str::lower($matches[0]);
        }

        return null;
    }
}
