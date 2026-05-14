<x-layouts.app title="Account Billing">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide text-teal-700">Account billing</div>
            <h1 class="app-page-title mt-1">Billing</h1>
            <p class="mt-1 text-sm text-slate-500">SaaS subscription charges for {{ $company->name }}.</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active machines" :value="$activeMachineCount" tone="teal" />
        <x-stat label="Price per machine" :value="$setting->currency.' '.number_format((float) $monthlyMachineRate, 2)" tone="blue" />
        <x-stat label="Estimated monthly total" :value="$setting->currency.' '.number_format((float) $estimatedMonthlyTotal, 2)" tone="slate" />
        <x-stat label="Latest invoice" :value="$latestInvoice ? $latestInvoice->currency.' '.number_format((float) $latestInvoice->total, 2) : 'None yet'" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="app-panel rounded-xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-black text-slate-950">Payment Method</h2>
                    <p class="mt-1 text-sm text-slate-500">Direct Debit collection status for this account.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $company->gocardless_mandate_id ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    {{ $company->gocardless_mandate_id ? 'Active' : 'Not set up' }}
                </span>
            </div>
            <dl class="mt-5 grid gap-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Mandate status</dt><dd class="text-slate-950">{{ $company->gocardless_mandate_status ?: 'Not started' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Mandate reference</dt><dd class="font-mono text-xs text-slate-950">{{ $company->gocardless_mandate_id ?: 'Not available' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Confirmed</dt><dd class="text-slate-950">{{ $company->gocardless_mandate_confirmed_at?->format('d M Y H:i') ?? 'Not confirmed' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Payment terms</dt><dd class="text-slate-950">{{ $setting->payment_terms_days }} days</dd></div>
            </dl>
            @if(! $company->gocardless_mandate_id)
                <div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                    Direct Debit is not active yet. Contact support if you need a new mandate link.
                </div>
            @endif
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-base font-black text-slate-950">Billing Summary</h2>
            <p class="mt-1 text-sm text-slate-500">Your charge is based on the active machine count captured each month.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Snapshot day</div>
                    <div class="mt-1 text-2xl font-black text-slate-950">Day {{ $setting->snapshot_day }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Rate source</div>
                    <div class="mt-1 text-sm font-black text-slate-950">{{ $company->monthly_machine_rate_override !== null ? 'Account rate' : 'Standard rate' }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Currency</div>
                    <div class="mt-1 text-2xl font-black text-slate-950">{{ $setting->currency }}</div>
                </div>
            </div>

            <div class="mt-5 app-table-wrap">
                <table class="app-table">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th>Period</th><th>Snapshot</th><th>Machines</th><th>Rate</th></tr>
                    </thead>
                    <tbody>
                    @forelse($snapshots as $snapshot)
                        <tr>
                            <td>{{ $snapshot->period_start->format('d M Y') }} - {{ $snapshot->period_end->format('d M Y') }}</td>
                            <td>{{ $snapshot->snapshot_date->format('d M Y') }}</td>
                            <td>{{ $snapshot->active_machine_count }}</td>
                            <td>{{ $snapshot->currency }} {{ number_format((float) $snapshot->monthly_machine_rate, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-slate-500">No billing snapshots have been captured yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="app-panel mt-6 rounded-xl p-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-slate-950">Invoices</h2>
                <p class="mt-1 text-sm text-slate-500">SaaS invoices and payment collection status for your account.</p>
            </div>
        </div>

        <div class="app-table-wrap mt-4">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th>Invoice</th><th>Period</th><th>Due</th><th>Machines</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="font-mono text-xs">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->period_start->format('d M Y') }} - {{ $invoice->period_end->format('d M Y') }}</td>
                        <td>{{ $invoice->due_date?->format('d M Y') ?? 'Not set' }}</td>
                        <td>{{ $invoice->active_machine_count }}</td>
                        <td>{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
                        <td><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ ucfirst($invoice->status) }}</span></td>
                        <td>
                            @if($invoice->gocardless_payment_id)
                                <div class="font-mono text-xs text-slate-700">{{ $invoice->gocardless_payment_id }}</div>
                                <div class="text-xs text-slate-500">{{ $invoice->gocardless_payment_status ?: 'Requested' }}{{ $invoice->gocardless_charge_date ? ' / '.$invoice->gocardless_charge_date->format('d M Y') : '' }}</div>
                            @else
                                <span class="text-sm text-slate-500">Not requested</span>
                            @endif
                        </td>
                        <td><a class="text-sm font-bold text-teal-700 hover:text-teal-900" href="{{ route('billing.invoices.pdf', $invoice) }}">Download PDF</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-slate-500">No invoices have been issued yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </section>
</x-layouts.app>
