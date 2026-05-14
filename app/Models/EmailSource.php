<?php

namespace App\Models;

use Database\Factories\EmailSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSource extends Model
{
    public const PROVIDER_GMAIL = 'gmail';

    public const PROVIDER_OFFICE365 = 'office365';

    public const PROVIDER_CUSTOM_IMAP = 'custom_imap';

    public const AUTH_BASIC = 'basic';

    public const AUTH_MICROSOFT_GRAPH = 'microsoft_graph';

    public const AUTH_WEBHOOK = 'webhook';

    /** @use HasFactory<EmailSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'provider',
        'auth_type',
        'mailbox_email',
        'username',
        'password',
        'oauth_tenant_id',
        'oauth_client_id',
        'oauth_client_secret',
        'oauth_scope',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
        'oauth_status',
        'imap_host',
        'imap_port',
        'encryption',
        'folder',
        'mark_as_seen',
        'delete_after_ingest',
        'configuration',
        'is_active',
        'last_checked_at',
        'last_success_at',
        'last_error',
    ];

    protected $hidden = [
        'password',
        'oauth_client_secret',
        'oauth_access_token',
        'oauth_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'oauth_client_secret' => 'encrypted',
            'oauth_access_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_token_expires_at' => 'datetime',
            'configuration' => 'array',
            'mark_as_seen' => 'boolean',
            'delete_after_ingest' => 'boolean',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function providers(): array
    {
        return [
            self::PROVIDER_GMAIL => 'Gmail',
            self::PROVIDER_OFFICE365 => 'Office 365',
            self::PROVIDER_CUSTOM_IMAP => 'Custom Mail Server',
        ];
    }

    public static function webhookProviders(): array
    {
        return [
            'mailgun' => 'Mailgun inbound route',
            'sendgrid' => 'SendGrid inbound parse',
            'postmark' => 'Postmark inbound webhook',
            'generic' => 'Generic HTTP POST',
        ];
    }

    public function mailboxString(): ?string
    {
        if ($this->auth_type === self::AUTH_MICROSOFT_GRAPH || $this->auth_type === self::AUTH_WEBHOOK) {
            return null;
        }

        if (! $this->imap_host) {
            return null;
        }

        $protocol = $this->configuration['mailbox_protocol'] ?? 'imap';
        $flags = $protocol === 'pop3' ? '/pop3' : '/imap';

        if ($this->encryption === 'ssl') {
            $flags .= '/ssl';
        }

        if ($this->encryption === 'tls') {
            $flags .= '/tls';
        }

        return sprintf('{%s:%d%s}%s', $this->imap_host, $this->imap_port ?: 993, $flags, $this->folder ?: 'INBOX');
    }

    public function mailboxProtocol(): string
    {
        return $this->configuration['mailbox_protocol'] ?? 'imap';
    }

    public function usesMicrosoftGraph(): bool
    {
        return $this->auth_type === self::AUTH_MICROSOFT_GRAPH || $this->provider === self::PROVIDER_OFFICE365;
    }

    public function usesWebhookDelivery(): bool
    {
        return $this->auth_type === self::AUTH_WEBHOOK;
    }

    public function webhookProvider(): string
    {
        return $this->configuration['webhook_provider'] ?? 'generic';
    }
}
