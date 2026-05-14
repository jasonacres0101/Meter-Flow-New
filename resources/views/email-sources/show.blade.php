<x-layouts.app :title="$emailSource->name">
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-black">{{ $emailSource->name }}</h1><p class="mt-1 text-sm text-slate-500">{{ \App\Models\EmailSource::providers()[$emailSource->provider] ?? $emailSource->provider }} / {{ $emailSource->mailbox_email }}</p></div>
        <div class="flex gap-2">
            <form method="post" action="{{ route('email-sources.test', $emailSource) }}">
                @csrf
                <button class="app-button-secondary">Test connection</button>
            </form>
            <a href="{{ route('email-sources.edit', $emailSource) }}" class="app-button-secondary">Edit</a>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <x-stat label="Provider" :value="\App\Models\EmailSource::providers()[$emailSource->provider] ?? $emailSource->provider" tone="blue" />
        <x-stat label="Auth" :value="$emailSource->usesMicrosoftGraph() ? 'Microsoft Graph' : ($emailSource->usesWebhookDelivery() ? 'Webhook' : strtoupper($emailSource->mailboxProtocol() === 'pop3' ? 'pop' : 'imap'))" tone="teal" />
        <x-stat label="Status" :value="$emailSource->is_active ? 'Active' : 'Inactive'" tone="slate" />
    </div>
    <div class="app-panel mt-6 rounded-xl p-5">
        <h2 class="text-lg font-black">Connection details</h2>
        <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="font-bold text-slate-500">Owner</dt><dd>{{ $emailSource->company?->name ?? 'Platform master mailbox' }}</dd></div>
            <div><dt class="font-bold text-slate-500">Username</dt><dd>{{ $emailSource->username }}</dd></div>
            <div><dt class="font-bold text-slate-500">Folder</dt><dd>{{ $emailSource->folder }}</dd></div>
            @if($emailSource->usesMicrosoftGraph())
                <div><dt class="font-bold text-slate-500">Tenant ID</dt><dd class="break-all">{{ $emailSource->oauth_tenant_id }}</dd></div>
                <div><dt class="font-bold text-slate-500">Client ID</dt><dd class="break-all">{{ $emailSource->oauth_client_id }}</dd></div>
                <div><dt class="font-bold text-slate-500">Graph scope</dt><dd class="break-all">{{ $emailSource->oauth_scope }}</dd></div>
                <div><dt class="font-bold text-slate-500">OAuth status</dt><dd>{{ str_replace('_', ' ', $emailSource->oauth_status) }}</dd></div>
            @elseif($emailSource->usesWebhookDelivery())
                @php($webhookProvider = $emailSource->webhookProvider())
                <div><dt class="font-bold text-slate-500">Inbound provider</dt><dd>{{ \App\Models\EmailSource::webhookProviders()[$webhookProvider] ?? $webhookProvider }}</dd></div>
                <div><dt class="font-bold text-slate-500">Endpoint</dt><dd class="break-all">{{ route('inbound.'.$webhookProvider) }}</dd></div>
                <div><dt class="font-bold text-slate-500">Authentication</dt><dd>Send the secret in the X-Email-Source-Token header or token query parameter.</dd></div>
            @else
                <div><dt class="font-bold text-slate-500">Protocol</dt><dd>{{ strtoupper($emailSource->mailboxProtocol() === 'pop3' ? 'pop' : 'imap') }}</dd></div>
                <div><dt class="font-bold text-slate-500">Mailbox string</dt><dd class="break-all">{{ $emailSource->mailboxString() }}</dd></div>
            @endif
            <div><dt class="font-bold text-slate-500">Last checked</dt><dd>{{ $emailSource->last_checked_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
            <div><dt class="font-bold text-slate-500">Last success</dt><dd>{{ $emailSource->last_success_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
        </dl>
        @if($emailSource->last_error)<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $emailSource->last_error }}</div>@endif
    </div>
</x-layouts.app>
