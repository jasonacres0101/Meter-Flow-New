<x-layouts.app title="SaaS Billing">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="app-page-title">SaaS Billing</h1>
            <p class="mt-1 text-sm text-slate-500">Bill accounts from the machine count captured on the 25th, then invoice at month end.</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <x-stat label="Monthly machine rate" :value="$setting->currency.' '.number_format((float) $setting->monthly_machine_rate, 2)" tone="blue" />
        <x-stat label="Snapshot day" :value="'Day '.$setting->snapshot_day" tone="teal" />
        <x-stat label="Invoices this month" :value="$setting->currency.' '.number_format((float) $currentMonthInvoicesTotal, 2)" tone="slate" />
        <x-stat label="Payment terms" :value="$setting->payment_terms_days.' days'" tone="amber" />
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-base font-black">Billing settings</h2>
            <form method="post" action="{{ route('billing.update') }}" class="mt-4 grid gap-4">
                @csrf
                @method('PUT')
                <label class="app-field">Monthly price per active machine
                    <input name="monthly_machine_rate" type="number" min="0" step="0.01" value="{{ old('monthly_machine_rate', $setting->monthly_machine_rate) }}" class="app-field-control">
                </label>
                <label class="app-field">Currency
                    <input name="currency" maxlength="3" value="{{ old('currency', $setting->currency) }}" class="app-field-control">
                </label>
                <label class="app-field">Snapshot day
                    <input name="snapshot_day" type="number" min="1" max="28" value="{{ old('snapshot_day', $setting->snapshot_day) }}" class="app-field-control">
                </label>
                <label class="app-field">Payment terms days
                    <input name="payment_terms_days" type="number" min="0" max="90" value="{{ old('payment_terms_days', $setting->payment_terms_days) }}" class="app-field-control">
                </label>
                <div class="border-t border-slate-200 pt-4">
                    <h3 class="text-sm font-black text-slate-950">GoCardless payment settings</h3>
                    <p class="mt-1 text-xs text-slate-500">Used for collecting SaaS account invoices by Direct Debit. Use sandbox while testing, then switch to live when ready.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="gocardless_enabled" value="1" @checked(old('gocardless_enabled', $setting->gocardless_enabled))>
                    Enable GoCardless collection
                </label>
                <label class="app-field">Environment
                    <select name="gocardless_environment" class="app-field-control">
                        <option value="sandbox" @selected(old('gocardless_environment', $setting->gocardless_environment) === 'sandbox')>Sandbox</option>
                        <option value="live" @selected(old('gocardless_environment', $setting->gocardless_environment) === 'live')>Live</option>
                    </select>
                </label>
                <label class="app-field">Access token
                    <input name="gocardless_access_token" type="password" class="app-field-control" placeholder="{{ $setting->gocardless_access_token ? 'Leave blank to keep existing token' : 'GoCardless access token' }}">
                </label>
                <label class="app-field">Webhook secret
                    <input name="gocardless_webhook_secret" type="password" class="app-field-control" placeholder="{{ $setting->gocardless_webhook_secret ? 'Leave blank to keep existing secret' : 'GoCardless webhook secret' }}">
                </label>
                <label class="app-field">Creditor ID
                    <input name="gocardless_creditor_id" value="{{ old('gocardless_creditor_id', $setting->gocardless_creditor_id) }}" class="app-field-control" placeholder="Optional, filled by test if available">
                </label>
                @if ($errors->any())<div class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                <button class="app-button">Save billing settings</button>
            </form>
            <form method="post" action="{{ route('billing.gocardless.test') }}" class="mt-3">
                @csrf
                <button class="app-button-secondary">Test GoCardless connection</button>
            </form>
            <dl class="mt-4 grid gap-2 text-xs text-slate-600">
                <div class="flex justify-between gap-3"><dt>Last tested</dt><dd>{{ $setting->gocardless_last_tested_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Last success</dt><dd>{{ $setting->gocardless_last_success_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Creditor ID</dt><dd class="font-mono">{{ $setting->gocardless_creditor_id ?: 'Not set' }}</dd></div>
            </dl>
            @if($setting->gocardless_last_error)
                <div class="mt-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{{ $setting->gocardless_last_error }}</div>
            @endif
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-base font-black">Billing run controls</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">The scheduler runs daily. Machine counts are captured only on the configured snapshot day, and invoices are generated only on the last day of the month. These controls let SaaS admin run a period manually.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <form method="post" action="{{ route('billing.capture') }}" class="rounded-lg border border-slate-200 p-4">
                    @csrf
                    <label class="app-field">Snapshot date
                        <input name="date" type="date" value="{{ now()->setDay(min($setting->snapshot_day, now()->daysInMonth))->toDateString() }}" class="app-field-control">
                    </label>
                    <button class="app-button-secondary mt-4">Capture machine counts</button>
                </form>
                <form method="post" action="{{ route('billing.generate') }}" class="rounded-lg border border-slate-200 p-4">
                    @csrf
                    <label class="app-field">Invoice date
                        <input name="date" type="date" value="{{ now()->endOfMonth()->toDateString() }}" class="app-field-control">
                    </label>
                    <button class="app-button-secondary mt-4">Generate invoices</button>
                </form>
            </div>
        </section>
    </div>

    <section class="app-panel mt-6 rounded-xl p-5">
        <h2 class="text-base font-black">Latest invoices</h2>
        <div class="app-table-wrap mt-4">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th>Invoice</th><th>Account</th><th>Period</th><th>Machines</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                        <td class="font-bold">{{ $invoice->company->name }}</td>
                        <td>{{ $invoice->period_start->format('d M Y') }} - {{ $invoice->period_end->format('d M Y') }}</td>
                        <td>{{ $invoice->active_machine_count }}</td>
                        <td>{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
                        <td><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ ucfirst($invoice->status) }}</span></td>
                        <td>
                            @if($invoice->gocardless_payment_id)
                                <div class="font-mono text-xs text-slate-700">{{ $invoice->gocardless_payment_id }}</div>
                                <div class="text-xs text-slate-500">{{ $invoice->gocardless_payment_status ?: 'Requested' }}{{ $invoice->gocardless_charge_date ? ' / '.$invoice->gocardless_charge_date->format('d M Y') : '' }}</div>
                            @elseif($invoice->company->gocardless_mandate_id)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Ready to collect</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">No mandate</span>
                            @endif
                            @if($invoice->gocardless_payment_error)
                                <div class="mt-1 max-w-xs text-xs text-red-600">{{ $invoice->gocardless_payment_error }}</div>
                            @endif
                        </td>
                        <td>
                            <a class="text-sm font-bold text-teal-700 hover:text-teal-900" href="{{ route('billing.invoices.pdf', $invoice) }}">PDF</a>
                            @if(! $invoice->gocardless_payment_id && $invoice->company->gocardless_mandate_id)
                                <form method="post" action="{{ route('billing.invoices.collect', $invoice) }}">
                                    @csrf
                                    <button class="app-button-secondary mt-2 whitespace-nowrap">Collect</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">No invoices generated yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </section>

    <section class="app-panel mt-6 rounded-xl p-5">
        <h2 class="text-base font-black">Latest machine-count snapshots</h2>
        <div class="app-table-wrap mt-4">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th>Account</th><th>Period</th><th>Snapshot date</th><th>Machines</th><th>Rate</th></tr>
                </thead>
                <tbody>
                @forelse($snapshots as $snapshot)
                    <tr>
                        <td class="font-bold">{{ $snapshot->company->name }}</td>
                        <td>{{ $snapshot->period_start->format('d M Y') }} - {{ $snapshot->period_end->format('d M Y') }}</td>
                        <td>{{ $snapshot->snapshot_date->format('d M Y') }}</td>
                        <td>{{ $snapshot->active_machine_count }}</td>
                        <td>{{ $snapshot->currency }} {{ number_format((float) $snapshot->monthly_machine_rate, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-slate-500">No machine-count snapshots captured yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $snapshots->links() }}</div>
    </section>
</x-layouts.app>
