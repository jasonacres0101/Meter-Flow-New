<?php

namespace App\Console\Commands;

use App\Models\EmailSource;
use Illuminate\Console\Command;

class PollMicrosoftGraphReports extends Command
{
    protected $signature = 'reports:poll-microsoft-graph {--source= : Poll one Office 365 email source ID only}';

    protected $description = 'Poll Office 365 report mailboxes using Microsoft Graph modern authentication.';

    public function handle(): int
    {
        $sources = EmailSource::query()
            ->where('provider', EmailSource::PROVIDER_OFFICE365)
            ->where('is_active', true)
            ->when($this->option('source'), fn ($query, $sourceId) => $query->whereKey($sourceId))
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No active Office 365 email sources found.');

            return self::SUCCESS;
        }

        foreach ($sources as $source) {
            $source->forceFill(['last_checked_at' => now()])->saveQuietly();

            if (! $source->oauth_tenant_id || ! $source->oauth_client_id || ! $source->oauth_client_secret) {
                $source->forceFill([
                    'oauth_status' => 'missing_credentials',
                    'last_error' => 'Office 365 modern authentication requires tenant ID, client ID and client secret.',
                ])->saveQuietly();

                $this->error("{$source->name}: missing Microsoft Graph credentials.");

                continue;
            }

            $source->forceFill([
                'oauth_status' => 'configured',
                'last_error' => null,
            ])->saveQuietly();

            $this->line("{$source->name}: Microsoft Graph credentials are configured. Mail polling implementation can now exchange a token and read /users/{mailbox}/messages.");
        }

        return self::SUCCESS;
    }
}
