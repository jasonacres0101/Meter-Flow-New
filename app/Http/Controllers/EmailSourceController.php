<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailSource;
use App\Services\MicrosoftGraphMailClient;
use App\Services\Pop3MailboxClient;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailSourceController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmailSource::query();

        if ($request->user()->isPlatformAdmin()) {
            $query->whereNull('company_id');
        } else {
            Tenant::scope($query, $request->user());
        }

        return view('email-sources.index', [
            'emailSources' => $query->with('company')->latest()->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('email-sources.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        EmailSource::create($this->validated($request));

        return redirect()->route('email-sources.index')->with('status', 'Email source created.');
    }

    public function show(EmailSource $emailSource): View
    {
        $this->authorizeTenant($emailSource);

        return view('email-sources.show', ['emailSource' => $emailSource->load('company')]);
    }

    public function edit(Request $request, EmailSource $emailSource): View
    {
        $this->authorizeTenant($emailSource);

        return view('email-sources.edit', array_merge($this->formData($request), ['emailSource' => $emailSource]));
    }

    public function update(Request $request, EmailSource $emailSource): RedirectResponse
    {
        $this->authorizeTenant($emailSource);
        $data = $this->validated($request, $emailSource);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (blank($data['oauth_client_secret'] ?? null)) {
            unset($data['oauth_client_secret']);
        }

        $emailSource->update($data);

        return redirect()->route('email-sources.show', $emailSource)->with('status', 'Email source updated.');
    }

    public function destroy(EmailSource $emailSource): RedirectResponse
    {
        $this->authorizeTenant($emailSource);
        $emailSource->update(['is_active' => false]);

        return redirect()->route('email-sources.index')->with('status', 'Email source deactivated.');
    }

    public function test(EmailSource $emailSource, MicrosoftGraphMailClient $graph, Pop3MailboxClient $pop3): RedirectResponse
    {
        $this->authorizeTenant($emailSource);

        $emailSource->update(['last_checked_at' => now()]);

        try {
            $testedLiveConnection = true;
            $status = 'Email source test succeeded.';

            if ($emailSource->usesMicrosoftGraph()) {
                $graph->testMailbox($emailSource);
            } elseif ($emailSource->usesWebhookDelivery()) {
                $status = $this->testWebhookSource($emailSource);
                $testedLiveConnection = false;
            } elseif ($emailSource->mailboxProtocol() === 'pop3') {
                $pop3->testConnection($emailSource);
            } else {
                $status = $this->testImapMailbox($emailSource) ?: $status;
                $testedLiveConnection = $status === 'Email source test succeeded.';
            }

            $updates = [
                'last_error' => null,
                'oauth_status' => $emailSource->usesMicrosoftGraph() ? 'connected' : $emailSource->oauth_status,
            ];

            if ($testedLiveConnection) {
                $updates['last_success_at'] = now();
            }

            $emailSource->update($updates);

            return back()->with('status', $status);
        } catch (Throwable $exception) {
            $emailSource->update([
                'last_error' => $exception->getMessage(),
                'oauth_status' => $emailSource->usesMicrosoftGraph() ? 'failed' : $emailSource->oauth_status,
            ]);

            return back()->withErrors(['email_source' => 'Email source test failed: '.$exception->getMessage()]);
        }
    }

    private function formData(Request $request): array
    {
        return [
            'companies' => $request->user()->isPlatformAdmin() ? collect() : collect([$request->user()->company]),
            'providers' => EmailSource::providers(),
            'encryptions' => ['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'],
            'webhookProviders' => EmailSource::webhookProviders(),
        ];
    }

    private function validated(Request $request, ?EmailSource $emailSource = null): array
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', Rule::in(array_keys(EmailSource::providers()))],
            'mailbox_email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'oauth_tenant_id' => ['nullable', 'string', 'max:255'],
            'oauth_client_id' => ['nullable', 'string', 'max:255'],
            'oauth_client_secret' => ['nullable', 'string', 'max:255'],
            'oauth_scope' => ['nullable', 'string', 'max:255'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', Rule::in(['ssl', 'tls', 'none'])],
            'folder' => ['required', 'string', 'max:255'],
            'mark_as_seen' => ['nullable', 'boolean'],
            'delete_after_ingest' => ['nullable', 'boolean'],
            'configuration' => ['nullable', 'json'],
            'delivery_method' => ['nullable', Rule::in(['imap', 'pop3', 'webhook'])],
            'webhook_provider' => ['nullable', Rule::in(array_keys(EmailSource::webhookProviders()))],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['company_id'] = $request->user()->isPlatformAdmin() ? null : $request->user()->company_id;
        abort_unless($request->user()->isPlatformAdmin() || $data['company_id'] === $request->user()->company_id, 403);

        $providerDefaults = $this->providerDefaults($data['provider']);
        $isOffice365 = $data['provider'] === EmailSource::PROVIDER_OFFICE365;
        $usesWebhook = ! $isOffice365
            && $data['provider'] === EmailSource::PROVIDER_CUSTOM_IMAP
            && ($data['delivery_method'] ?? 'imap') === 'webhook';
        $usesPop3 = ! $isOffice365
            && $data['provider'] === EmailSource::PROVIDER_CUSTOM_IMAP
            && ($data['delivery_method'] ?? 'imap') === 'pop3';
        $data['auth_type'] = $isOffice365 ? EmailSource::AUTH_MICROSOFT_GRAPH : ($usesWebhook ? EmailSource::AUTH_WEBHOOK : EmailSource::AUTH_BASIC);
        $data['imap_host'] = ($data['imap_host'] ?? null) ?: $providerDefaults['host'];
        $data['imap_port'] = ($data['imap_port'] ?? null) ?: $providerDefaults['port'];
        $data['encryption'] = ($data['encryption'] ?? null) ?: $providerDefaults['encryption'];
        $data['username'] = ($data['username'] ?? null) ?: $data['mailbox_email'];
        $data['oauth_scope'] = ($data['oauth_scope'] ?? null) ?: $providerDefaults['scope'];
        $data['oauth_status'] = $isOffice365 ? 'not_connected' : 'not_required';
        $data['mark_as_seen'] = $request->boolean('mark_as_seen');
        $data['delete_after_ingest'] = $request->boolean('delete_after_ingest');
        $data['is_active'] = $request->boolean('is_active');
        $data['configuration'] = filled($data['configuration'] ?? null) ? json_decode($data['configuration'], true) : [];
        $data['configuration']['webhook_provider'] = $usesWebhook ? ($data['webhook_provider'] ?: 'generic') : null;
        $data['configuration']['mailbox_protocol'] = $usesWebhook ? null : ($usesPop3 ? 'pop3' : 'imap');
        $data['configuration'] = array_filter($data['configuration'], fn ($value) => ! is_null($value));

        if ($isOffice365) {
            $request->validate([
                'oauth_tenant_id' => ['required', 'string', 'max:255'],
                'oauth_client_id' => ['required', 'string', 'max:255'],
                'oauth_client_secret' => [$emailSource ? 'nullable' : 'required', 'string', 'max:255'],
            ]);

            $data['password'] = null;
            $data['imap_host'] = null;
            $data['imap_port'] = null;
            $data['encryption'] = null;
        } elseif ($usesWebhook) {
            $request->validate([
                'webhook_provider' => ['required', Rule::in(array_keys(EmailSource::webhookProviders()))],
                'webhook_secret' => [$emailSource ? 'nullable' : 'required', 'string', 'max:255'],
            ]);

            $data['username'] = null;
            $data['password'] = $data['webhook_secret'] ?? null;
            $data['imap_host'] = null;
            $data['imap_port'] = null;
            $data['encryption'] = null;
            $data['oauth_tenant_id'] = null;
            $data['oauth_client_id'] = null;
            $data['oauth_client_secret'] = null;
            $data['oauth_scope'] = null;
            $data['oauth_access_token'] = null;
            $data['oauth_refresh_token'] = null;
            $data['oauth_token_expires_at'] = null;
            $data['oauth_status'] = 'not_required';
        } else {
            $request->validate([
                'password' => [$emailSource ? 'nullable' : 'required', 'string', 'max:255'],
            ]);

            $data['oauth_tenant_id'] = null;
            $data['oauth_client_id'] = null;
            $data['oauth_client_secret'] = null;
            $data['oauth_scope'] = null;
            $data['oauth_access_token'] = null;
            $data['oauth_refresh_token'] = null;
            $data['oauth_token_expires_at'] = null;
            $data['oauth_status'] = 'not_required';
        }

        unset($data['delivery_method'], $data['webhook_provider'], $data['webhook_secret']);

        return $data;
    }

    private function providerDefaults(string $provider): array
    {
        return match ($provider) {
            EmailSource::PROVIDER_GMAIL => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl', 'scope' => null],
            EmailSource::PROVIDER_OFFICE365 => ['host' => null, 'port' => null, 'encryption' => null, 'scope' => 'https://graph.microsoft.com/.default'],
            default => ['host' => null, 'port' => 993, 'encryption' => 'ssl', 'scope' => null],
        };
    }

    private function authorizeTenant(EmailSource $emailSource): void
    {
        abort_unless(
            (request()->user()->isPlatformAdmin() && is_null($emailSource->company_id))
            || $emailSource->company_id === request()->user()->company_id,
            403
        );
    }

    private function testImapMailbox(EmailSource $emailSource): ?string
    {
        if (! $emailSource->mailboxString() || ! $emailSource->username || ! $emailSource->password) {
            throw new \RuntimeException('Mailbox host, username and password are required for mailbox testing.');
        }

        $protocol = $emailSource->mailboxProtocol() === 'pop3' ? 'POP' : 'IMAP';

        if (! function_exists('imap_open')) {
            return "Email source settings look valid. Live {$protocol} testing is unavailable because the PHP IMAP extension is not installed on this server.";
        }

        $connection = @imap_open($emailSource->mailboxString(), $emailSource->username, $emailSource->password, OP_READONLY, 1);

        if (! $connection) {
            throw new \RuntimeException(imap_last_error() ?: 'Unable to connect to the mailbox.');
        }

        imap_close($connection);

        return null;
    }

    private function testWebhookSource(EmailSource $emailSource): string
    {
        if (! $emailSource->mailbox_email || ! $emailSource->password) {
            throw new \RuntimeException('Mailbox email and webhook secret are required for webhook delivery.');
        }

        return 'Webhook email source is ready. Send inbound email payloads to the configured endpoint with this source secret.';
    }
}
