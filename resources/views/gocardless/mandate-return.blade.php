<x-layouts.app title="Direct Debit setup">
    <div class="mx-auto max-w-2xl">
        <section class="app-panel rounded-xl p-6">
            <div class="text-xs font-bold uppercase tracking-wide text-teal-700">GoCardless</div>
            <h1 class="app-page-title mt-2">Direct Debit setup</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Thanks. We have received the return from GoCardless for {{ $company->name }}.
            </p>

            <dl class="mt-5 grid gap-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Status</dt><dd class="text-slate-950">{{ $company->gocardless_mandate_status ?: 'Pending' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500">Mandate ID</dt><dd class="font-mono text-slate-950">{{ $company->gocardless_mandate_id ?: 'Awaiting confirmation' }}</dd></div>
            </dl>

            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                If this page still shows pending, the SaaS admin can refresh the account status from the account details page.
            </div>
        </section>
    </div>
</x-layouts.app>
