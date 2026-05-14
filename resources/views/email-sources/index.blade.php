<x-layouts.app title="Email Sources">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black">Email Sources</h1>
            <p class="mt-1 text-sm text-slate-500">Configure company mailboxes and the platform master inbox used for reusable model templates.</p>
        </div>
        <a href="{{ route('email-sources.create') }}" class="app-button">Add source</a>
    </div>

    <div class="app-panel overflow-hidden rounded-xl">
        <table class="app-table">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr><th>Name</th><th>Provider</th><th>Mailbox</th><th>Connection</th><th>Pull status</th><th>Test</th></tr>
            </thead>
            <tbody>
            @foreach($emailSources as $source)
                <tr>
                    <td><a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('email-sources.show', $source) }}">{{ $source->name }}</a><div class="text-xs text-slate-500">{{ $source->company?->name ?? 'Platform master mailbox' }}</div></td>
                    <td>{{ \App\Models\EmailSource::providers()[$source->provider] ?? $source->provider }}</td>
                    <td>{{ $source->mailbox_email }}</td>
                    <td>
                        @if($source->usesWebhookDelivery())
                            Webhook / {{ \App\Models\EmailSource::webhookProviders()[$source->webhookProvider()] ?? $source->webhookProvider() }}
                        @elseif($source->usesMicrosoftGraph())
                            Microsoft Graph
                        @else
                            {{ strtoupper($source->mailboxProtocol() === 'pop3' ? 'pop' : 'imap') }} / {{ $source->imap_host }}:{{ $source->imap_port }}
                        @endif
                    </td>
                    <td>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $source->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $source->is_active ? 'Active' : 'Inactive' }}</span>
                        <div class="mt-2 text-xs {{ $source->last_error ? 'text-red-700' : 'text-slate-500' }}">
                            @if($source->last_success_at)
                                Last pulled {{ $source->last_success_at->format('d M H:i') }}
                            @elseif($source->last_checked_at)
                                Checked {{ $source->last_checked_at->format('d M H:i') }}
                            @else
                                Never checked
                            @endif
                        </div>
                        @if($source->last_error)
                            <div class="mt-1 max-w-xs truncate text-xs font-semibold text-red-700" title="{{ $source->last_error }}">{{ $source->last_error }}</div>
                        @endif
                    </td>
                    <td>
                        <form method="post" action="{{ route('email-sources.test', $source) }}">
                            @csrf
                            <button class="app-button-secondary">Test</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $emailSources->links() }}</div>
</x-layouts.app>
