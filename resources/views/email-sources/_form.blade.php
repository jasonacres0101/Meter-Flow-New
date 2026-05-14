@php
    $selectedProvider = old('provider', $emailSource->provider ?? 'gmail');
    $selectedDeliveryMethod = old('delivery_method', isset($emailSource) && $emailSource->usesWebhookDelivery() ? 'webhook' : ($emailSource->configuration['mailbox_protocol'] ?? 'imap'));
    $selectedWebhookProvider = old('webhook_provider', $emailSource->configuration['webhook_provider'] ?? 'generic');
@endphp
@csrf
<div class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
    <div class="app-panel rounded-xl p-5">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold text-slate-700">Source name<input name="name" value="{{ old('name', $emailSource->name ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="text-sm font-semibold text-slate-700">Provider<select id="provider" name="provider" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">@foreach($providers as $value => $label)<option value="{{ $value }}" @selected($selectedProvider === $value)>{{ $label }}</option>@endforeach</select></label>
            @if(auth()->user()->isPlatformAdmin())
                <label class="text-sm font-semibold text-slate-700">Owner<select name="company_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"><option value="" @selected(blank(old('company_id', $emailSource->company_id ?? '')))>Platform master mailbox</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id', $emailSource->company_id ?? '') == $company->id)>{{ $company->name }}</option>@endforeach</select></label>
            @endif
            <label class="text-sm font-semibold text-slate-700 custom-field">Connection method<select id="delivery_method" name="delivery_method" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"><option value="imap" @selected($selectedDeliveryMethod === 'imap')>IMAP mailbox</option><option value="pop3" @selected($selectedDeliveryMethod === 'pop3')>POP mailbox</option><option value="webhook" @selected($selectedDeliveryMethod === 'webhook')>Webhook / inbound parse API</option></select></label>
            <label class="text-sm font-semibold text-slate-700">Mailbox email<input name="mailbox_email" value="{{ old('mailbox_email', $emailSource->mailbox_email ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="text-sm font-semibold text-slate-700 imap-field">Username<input name="username" value="{{ old('username', $emailSource->username ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="text-sm font-semibold text-slate-700 imap-field">Password / app password<input name="password" type="password" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="{{ isset($emailSource) ? 'Leave blank to keep existing' : '' }}"></label>
            <label class="text-sm font-semibold text-slate-700 imap-field">Mailbox host<input name="imap_host" value="{{ old('imap_host', $emailSource->imap_host ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="mail.example.com"></label>
            <label class="text-sm font-semibold text-slate-700 imap-field">Mailbox port<input name="imap_port" value="{{ old('imap_port', $emailSource->imap_port ?? 993) }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="text-sm font-semibold text-slate-700 imap-field">Encryption<select name="encryption" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">@foreach($encryptions as $value => $label)<option value="{{ $value }}" @selected(old('encryption', $emailSource->encryption ?? 'ssl') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold text-slate-700 webhook-field">Inbound provider<select name="webhook_provider" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">@foreach($webhookProviders as $value => $label)<option value="{{ $value }}" @selected($selectedWebhookProvider === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold text-slate-700 webhook-field">Webhook secret<input name="webhook_secret" type="password" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="{{ isset($emailSource) ? 'Leave blank to keep existing' : 'Shared secret or random token' }}"></label>
            <label class="text-sm font-semibold text-slate-700 graph-field">Microsoft tenant ID<input name="oauth_tenant_id" value="{{ old('oauth_tenant_id', $emailSource->oauth_tenant_id ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="Directory tenant ID"></label>
            <label class="text-sm font-semibold text-slate-700 graph-field">Application client ID<input name="oauth_client_id" value="{{ old('oauth_client_id', $emailSource->oauth_client_id ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="Azure app registration client ID"></label>
            <label class="text-sm font-semibold text-slate-700 graph-field">Client secret<input name="oauth_client_secret" type="password" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="{{ isset($emailSource) ? 'Leave blank to keep existing' : 'Azure client secret' }}"></label>
            <label class="text-sm font-semibold text-slate-700 graph-field">Graph scope<input name="oauth_scope" value="{{ old('oauth_scope', $emailSource->oauth_scope ?? 'https://graph.microsoft.com/.default') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="text-sm font-semibold text-slate-700">Folder<input name="folder" value="{{ old('folder', $emailSource->folder ?? 'INBOX') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
        </div>
        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="hidden" name="mark_as_seen" value="0"><input type="checkbox" name="mark_as_seen" value="1" @checked(old('mark_as_seen', $emailSource->mark_as_seen ?? true))> Mark as seen</label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="hidden" name="delete_after_ingest" value="0"><input type="checkbox" name="delete_after_ingest" value="1" @checked(old('delete_after_ingest', $emailSource->delete_after_ingest ?? false))> Delete after ingest</label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $emailSource->is_active ?? true))> Active</label>
        </div>
        <label class="mt-5 block text-sm font-semibold text-slate-700">Advanced configuration JSON<textarea name="configuration" class="mt-2 h-28 w-full rounded-lg border-zinc-300 px-3 py-2.5">{{ old('configuration', isset($emailSource) ? json_encode($emailSource->configuration, JSON_PRETTY_PRINT) : '{}') }}</textarea></label>
        @if ($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>@endif
        <button class="app-button mt-6">Save email source</button>
    </div>

    <aside class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black">Provider notes</h2>
        <div class="mt-4 space-y-4 text-sm text-slate-600">
            <div class="rounded-lg bg-teal-50 p-4"><strong class="text-teal-800">Gmail</strong><span class="mt-1 block">Uses `imap.gmail.com:993` with SSL. Use a Google app password or OAuth configuration when you harden production.</span></div>
            <div class="rounded-lg bg-blue-50 p-4"><strong class="text-blue-800">Office 365</strong><span class="mt-1 block">Uses Microsoft Graph modern authentication. Create an Azure app registration with mailbox read permissions, then add the tenant ID, client ID and client secret here.</span></div>
            <div class="rounded-lg bg-slate-100 p-4"><strong class="text-slate-800">Custom Mail Server</strong><span class="mt-1 block">Use IMAP or POP for hosted mailboxes, or webhook delivery for Mailgun, SendGrid, Postmark, and generic inbound HTTP payloads.</span></div>
            @if(auth()->user()->isPlatformAdmin())
                <div class="rounded-lg bg-amber-50 p-4"><strong class="text-amber-800">Platform master mailbox</strong><span class="mt-1 block">Choose this owner when you want a central inbox for sample reports used to build reusable master makes, models and parser templates.</span></div>
            @endif
        </div>
    </aside>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const provider = document.getElementById('provider');
        const deliveryMethod = document.getElementById('delivery_method');
        const syncFields = () => {
            const graph = provider.value === 'office365';
            const custom = provider.value === 'custom_imap';
            const webhook = custom && deliveryMethod.value === 'webhook';
            document.querySelectorAll('.graph-field').forEach((field) => field.classList.toggle('hidden', !graph));
            document.querySelectorAll('.custom-field').forEach((field) => field.classList.toggle('hidden', !custom || graph));
            document.querySelectorAll('.webhook-field').forEach((field) => field.classList.toggle('hidden', !webhook));
            document.querySelectorAll('.imap-field').forEach((field) => field.classList.toggle('hidden', graph || webhook));
        };
        provider.addEventListener('change', syncFields);
        deliveryMethod.addEventListener('change', syncFields);
        syncFields();
    });
</script>
