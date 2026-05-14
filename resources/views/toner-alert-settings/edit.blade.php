<x-layouts.app title="Toner Alerts">
    @php
        $notificationEmails = $setting->notification_emails;
        $notificationEmails = is_array($notificationEmails)
            ? $notificationEmails
            : collect(preg_split('/[\r\n,]+/', (string) $notificationEmails))->map(fn ($email) => trim($email))->filter()->values()->all();
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-black">Toner Alert Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Set company-wide toner warning and critical thresholds for dashboard alerts and parsed readings.</p>
    </div>

    <form method="post" action="{{ route('toner-alert-settings.update') }}" class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
        @csrf
        @method('PUT')

        <div class="app-panel rounded-xl p-5">
            @if(auth()->user()->isPlatformAdmin())
                <label class="mb-5 block text-sm font-semibold text-slate-700">Company
                    <select name="company_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5" onchange="window.location='{{ route('toner-alert-settings.edit') }}?company_id='+this.value">
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected($setting->company_id === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <input type="hidden" name="company_id" value="{{ $setting->company_id }}">
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700">Warning threshold %
                    <input name="warning_threshold" type="number" min="1" max="100" value="{{ old('warning_threshold', $setting->warning_threshold) }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                </label>
                <label class="text-sm font-semibold text-slate-700">Critical threshold %
                    <input name="critical_threshold" type="number" min="1" max="100" value="{{ old('critical_threshold', $setting->critical_threshold) }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                </label>
            </div>

            <div class="mt-6">
                <div class="text-sm font-bold text-slate-700">Alert colours</div>
                <div class="mt-3 grid gap-3 md:grid-cols-4">
                    @foreach(['black' => 'Black', 'cyan' => 'Cyan', 'magenta' => 'Magenta', 'yellow' => 'Yellow'] as $key => $label)
                        <label class="rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="alert_{{ $key }}" value="1" @checked(old('alert_'.$key, $setting->{'alert_'.$key}))>
                            <span class="ml-2">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="include_in_dashboard" value="1" @checked(old('include_in_dashboard', $setting->include_in_dashboard))> Show alerts on dashboard</label>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $setting->is_active))> Enable toner alerts</label>
            </div>

            <label class="mt-5 block text-sm font-semibold text-slate-700">Notification emails
                <textarea name="notification_emails" class="mt-2 h-24 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="service@example.com, alerts@example.com">{{ old('notification_emails', implode(', ', $notificationEmails)) }}</textarea>
            </label>

            @if ($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>@endif
            <button class="app-button mt-6">Save toner settings</button>
        </div>

        <aside class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">How alerts work</h2>
            <div class="mt-4 space-y-4 text-sm text-slate-600">
                <div class="rounded-lg bg-amber-50 p-4"><strong class="text-amber-800">Low toner</strong><span class="mt-1 block">Readings at or below the warning threshold are marked `LOW` and can appear on the dashboard.</span></div>
                <div class="rounded-lg bg-rose-50 p-4"><strong class="text-rose-800">Critical toner</strong><span class="mt-1 block">Readings at or below the critical threshold are marked `CRITICAL` for urgent follow-up.</span></div>
                <div class="rounded-lg bg-blue-50 p-4"><strong class="text-blue-800">Per-company control</strong><span class="mt-1 block">Each tenant company can use thresholds that match its service agreement.</span></div>
            </div>
        </aside>
    </form>
</x-layouts.app>
