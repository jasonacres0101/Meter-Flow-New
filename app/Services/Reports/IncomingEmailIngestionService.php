<?php

namespace App\Services\Reports;

use App\Jobs\ProcessIncomingReportEmail;
use App\Models\IncomingReportEmail;
use Illuminate\Support\Carbon;

class IncomingEmailIngestionService
{
    public function store(array $payload, bool $queue = true): IncomingReportEmail
    {
        $email = IncomingReportEmail::create([
            'from_email' => $payload['from_email'] ?? $payload['from'] ?? 'unknown@example.invalid',
            'company_id' => $payload['company_id'] ?? null,
            'to_email' => $payload['to_email'] ?? $payload['to'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'body_text' => $payload['body_text'] ?? $payload['text'] ?? $payload['stripped-text'] ?? '',
            'body_html' => $payload['body_html'] ?? $payload['html'] ?? null,
            'received_at' => isset($payload['received_at']) ? Carbon::parse($payload['received_at']) : now(),
            'raw_payload' => $payload['raw_payload'] ?? $payload,
            'parse_status' => IncomingReportEmail::STATUS_PENDING,
        ]);

        if ($queue) {
            ProcessIncomingReportEmail::dispatch($email);
        }

        return $email;
    }
}
