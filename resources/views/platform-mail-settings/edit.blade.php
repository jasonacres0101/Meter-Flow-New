<x-layouts.app title="Outbound Email">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="app-page-title">Outbound Email</h1>
            <p class="mt-1 text-sm text-slate-500">Platform Office 365 modern authentication for account creation and system emails.</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_0.75fr]">
        <form method="post" action="{{ route('platform-mail-settings.update') }}" class="app-panel rounded-xl p-5">
            @csrf
            @method('PUT')
            <div class="grid gap-4 md:grid-cols-2">
                <label class="app-field">From name
                    <input name="from_name" value="{{ old('from_name', $setting->from_name ?? 'Copier Monitor') }}" class="app-field-control">
                </label>
                <label class="app-field">From email
                    <input name="from_email" type="email" value="{{ old('from_email', $setting->from_email ?? '') }}" class="app-field-control" placeholder="copiermonitor@yourdomain.com">
                </label>
                <label class="app-field">Microsoft tenant ID
                    <input name="oauth_tenant_id" value="{{ old('oauth_tenant_id', $setting->oauth_tenant_id ?? '') }}" class="app-field-control">
                </label>
                <label class="app-field">Application client ID
                    <input name="oauth_client_id" value="{{ old('oauth_client_id', $setting->oauth_client_id ?? '') }}" class="app-field-control">
                </label>
                <label class="app-field">Client secret
                    <input name="oauth_client_secret" type="password" class="app-field-control" placeholder="{{ $setting ? 'Leave blank to keep existing' : 'Azure client secret' }}">
                </label>
                <label class="app-field">Graph scope
                    <input name="oauth_scope" value="{{ old('oauth_scope', $setting->oauth_scope ?? 'https://graph.microsoft.com/.default') }}" class="app-field-control">
                </label>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $setting->is_active ?? true))>
                Active
            </label>
            @if ($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>@endif
            <button class="app-button mt-5">Save settings</button>
        </form>

        <aside class="space-y-5">
            <div class="app-panel rounded-xl p-5">
                <h2 class="text-base font-black">Send test email</h2>
                <p class="mt-1 text-sm text-slate-500">Sends a real email through Microsoft Graph using the saved settings.</p>
                <form method="post" action="{{ route('platform-mail-settings.test') }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="app-field">Test recipient
                        <input name="test_recipient" type="email" value="{{ old('test_recipient', auth()->user()->email) }}" class="app-field-control">
                    </label>
                    <button class="app-button-secondary">Send test</button>
                </form>
            </div>
            <div class="app-panel rounded-xl p-5">
                <h2 class="text-base font-black">Status</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-500">Last tested</dt><dd>{{ $setting?->last_tested_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Last success</dt><dd>{{ $setting?->last_success_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">State</dt><dd>{{ $setting?->is_active ? 'Active' : 'Inactive' }}</dd></div>
                </dl>
                @if($setting?->last_error)<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $setting->last_error }}</div>@endif
            </div>
        </aside>
    </div>
</x-layouts.app>
