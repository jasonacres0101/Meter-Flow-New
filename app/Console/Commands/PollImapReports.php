<?php

namespace App\Console\Commands;

use App\Models\EmailSource;
use App\Services\Pop3MailboxClient;
use App\Services\Reports\IncomingEmailIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PollImapReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:poll-imap {--source= : Poll one email source ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll configured IMAP or POP mailboxes for copier report emails.';

    /**
     * Execute the console command.
     */
    public function handle(IncomingEmailIngestionService $ingestion, Pop3MailboxClient $pop3)
    {
        $sources = EmailSource::query()
            ->where('is_active', true)
            ->when($this->option('source'), fn ($query, $sourceId) => $query->whereKey($sourceId))
            ->get();

        if (! function_exists('imap_open')) {
            $error = 'The PHP IMAP extension is not installed. Enable it to poll IMAP copier report mailboxes. POP sources can still be polled with the native POP client.';

            $sources
                ->reject(fn (EmailSource $source) => $source->usesMicrosoftGraph() || $source->usesWebhookDelivery() || $source->mailboxProtocol() === 'pop3')
                ->each(fn (EmailSource $source) => $source->forceFill([
                    'last_checked_at' => now(),
                    'last_error' => $error,
                ])->saveQuietly());

            if ($sources->contains(fn (EmailSource $source) => $source->mailboxProtocol() === 'pop3')) {
                $this->warn('PHP IMAP extension is missing; IMAP sources will be skipped, POP sources will use the native POP client.');
            } else {
                $this->error($error);

                return self::FAILURE;
            }
        }

        if ($sources->isEmpty()) {
            $this->warn('No active email sources found. Falling back to COPIER_REPORTS_IMAP_* environment settings.');

            $source = new EmailSource([
                'name' => 'Environment IMAP mailbox',
                'mailbox_email' => config('copier_reports.imap.username'),
                'username' => config('copier_reports.imap.username'),
                'password' => config('copier_reports.imap.password'),
                'imap_host' => config('copier_reports.imap.host'),
                'imap_port' => config('copier_reports.imap.port'),
                'encryption' => config('copier_reports.imap.encryption'),
                'folder' => config('copier_reports.imap.folder'),
                'delete_after_ingest' => config('copier_reports.imap.delete_after_ingest'),
            ]);

            if (! $source->mailboxString() || ! $source->username || ! $source->password) {
                $this->error('Create an active Email Source setting, or set COPIER_REPORTS_IMAP_* environment values.');

                return self::FAILURE;
            }

            $sources = collect([$source]);
        }

        $total = 0;

        foreach ($sources as $source) {
            if ($source->usesMicrosoftGraph()) {
                $this->line("Skipping {$source->name}; Office 365 sources use Microsoft Graph via reports:poll-microsoft-graph.");

                continue;
            }

            if ($source->usesWebhookDelivery()) {
                $this->line("Skipping {$source->name}; webhook sources receive pushed emails through inbound routes.");

                continue;
            }

            if ($source->exists) {
                $source->forceFill(['last_checked_at' => now(), 'last_error' => null])->saveQuietly();
            }

            if ($source->mailboxProtocol() === 'pop3') {
                try {
                    $messages = $pop3->fetch($source);
                    $processedUids = $source->configuration['processed_pop3_uids'] ?? [];

                    foreach ($messages as $message) {
                        $ingestion->store([
                            'company_id' => $source->company_id,
                            'from_email' => $message['from_email'],
                            'to_email' => $message['to_email'],
                            'subject' => $message['subject'],
                            'body_text' => $message['body_text'],
                            'body_html' => $message['body_html'],
                            'received_at' => $message['received_at'],
                            'raw_payload' => [
                                'provider' => $source->provider,
                                'email_source_id' => $source->id,
                                'mailbox_protocol' => 'pop3',
                                'message_number' => $message['message_number'],
                                'message_id' => $message['message_id'],
                                'uid' => $message['uid'],
                            ],
                        ]);

                        $processedUids[] = $message['uid'];
                    }

                    if ($source->exists) {
                        $configuration = $source->configuration ?? [];
                        $configuration['processed_pop3_uids'] = array_values(array_slice(array_unique($processedUids), -500));

                        $source->forceFill([
                            'configuration' => $configuration,
                            'last_success_at' => now(),
                            'last_error' => null,
                        ])->saveQuietly();
                    }

                    $total += count($messages);
                    $this->line("{$source->name}: ingested ".count($messages).' POP message(s).');
                } catch (\Throwable $exception) {
                    if ($source->exists) {
                        $source->forceFill(['last_error' => $exception->getMessage()])->saveQuietly();
                    }

                    $this->error("Could not poll {$source->name}: {$exception->getMessage()}");
                }

                continue;
            }

            $connection = imap_open($source->mailboxString(), $source->username, $source->password);

            if (! $connection) {
                $error = imap_last_error() ?: 'Could not connect to the mailbox.';

                if ($source->exists) {
                    $source->forceFill(['last_error' => $error])->saveQuietly();
                }

                $this->error("Could not connect to {$source->name}: {$error}");

                continue;
            }

            $messages = imap_search($connection, 'UNSEEN') ?: [];

            foreach ($messages as $messageNumber) {
                $headers = imap_headerinfo($connection, $messageNumber);
                $body = imap_body($connection, $messageNumber) ?: '';
                $from = $headers->from[0] ?? null;
                $to = $headers->to[0] ?? null;

                $ingestion->store([
                    'company_id' => $source->company_id,
                    'from_email' => $this->mailboxAddress($from),
                    'to_email' => $this->mailboxAddress($to),
                    'subject' => isset($headers->subject) ? imap_utf8($headers->subject) : null,
                    'body_text' => trim(strip_tags(quoted_printable_decode($body))),
                    'received_at' => isset($headers->date) ? date('c', strtotime($headers->date)) : now()->toIso8601String(),
                    'raw_payload' => [
                        'provider' => $source->provider,
                        'email_source_id' => $source->id,
                        'message_number' => $messageNumber,
                        'message_id' => $headers->message_id ?? null,
                    ],
                ]);

                $source->delete_after_ingest
                    ? imap_delete($connection, (string) $messageNumber)
                    : ($source->mark_as_seen ? imap_setflag_full($connection, (string) $messageNumber, '\\Seen') : null);
            }

            imap_expunge($connection);
            imap_close($connection);
            if ($source->exists) {
                $source->forceFill(['last_success_at' => now(), 'last_error' => null])->saveQuietly();
            }

            $total += count($messages);
        }

        $this->info("Ingested {$total} mailbox report email(s).");

        return self::SUCCESS;
    }

    private function mailboxAddress(?object $mailbox): string
    {
        if (! $mailbox) {
            return 'unknown@example.invalid';
        }

        return Str::lower(($mailbox->mailbox ?? 'unknown').'@'.($mailbox->host ?? 'example.invalid'));
    }
}
