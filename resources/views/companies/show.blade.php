<x-layouts.app :title="$company->name">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide text-teal-700">SaaS account</div>
            <h1 class="app-page-title mt-1">{{ $company->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $company->account_reference ?: 'No account reference' }} / {{ $company->billing_email ?: 'No billing email' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('users.create', ['company_id' => $company->id]) }}" class="app-button-secondary">Add user</a>
            <a href="{{ route('companies.edit', $company) }}" class="app-button">Edit account</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Office users" :value="$officeUsers->count()" tone="teal" />
        <x-stat label="Engineers" :value="$engineers->count()" tone="blue" />
        <x-stat label="Clients" :value="$company->clients_count" tone="slate" />
        <x-stat label="Sites" :value="$company->sites_count" tone="teal" />
        <x-stat label="Machines" :value="$company->machines_count" tone="blue" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="app-panel rounded-xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Account Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Support and billing information for this company.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $company->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $company->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <dl class="mt-5 grid gap-4 text-sm">
                <div><dt class="font-bold text-slate-500">Billing email</dt><dd class="mt-1 text-slate-950">{{ $company->billing_email ?: 'Not set' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Monthly machine rate override</dt><dd class="mt-1 text-slate-950">{{ $company->monthly_machine_rate_override !== null ? 'GBP '.number_format((float) $company->monthly_machine_rate_override, 2) : 'Using global SaaS rate' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Phone</dt><dd class="mt-1 text-slate-950">{{ $company->phone ?: 'Not set' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Website</dt><dd class="mt-1 text-slate-950">@if($company->website)<a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ $company->website }}" target="_blank" rel="noopener">{{ $company->website }}</a>@else Not set @endif</dd></div>
                <div><dt class="font-bold text-slate-500">Company number</dt><dd class="mt-1 text-slate-950">{{ $company->company_number ?: 'Not set' }}</dd></div>
                <div><dt class="font-bold text-slate-500">VAT number</dt><dd class="mt-1 text-slate-950">{{ $company->vat_number ?: 'Not set' }}</dd></div>
                <div>
                    <dt class="font-bold text-slate-500">Address</dt>
                    <dd class="mt-1 whitespace-pre-line text-slate-950">{{ collect([$company->address_line_1, $company->address_line_2, $company->city, $company->county, $company->postcode, $company->country])->filter()->join("\n") ?: 'Not set' }}</dd>
                </div>
                <div><dt class="font-bold text-slate-500">Created</dt><dd class="mt-1 text-slate-950">{{ $company->created_at?->format('d M Y H:i') }}</dd></div>
                <div><dt class="font-bold text-slate-500">Last updated</dt><dd class="mt-1 text-slate-950">{{ $company->updated_at?->format('d M Y H:i') }}</dd></div>
                <div><dt class="font-bold text-slate-500">Notes</dt><dd class="mt-1 text-slate-700">{{ $company->notes ?: 'No account notes.' }}</dd></div>
            </dl>

            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-sm font-black text-slate-950">GoCardless Direct Debit</h3>
                        <p class="mt-1 text-xs text-slate-500">Mandate setup for SaaS invoice collection.</p>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $company->gocardless_mandate_id ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                        {{ $company->gocardless_mandate_id ? 'Mandate ready' : 'Mandate required' }}
                    </span>
                </div>
                <dl class="mt-4 grid gap-2 text-xs text-slate-600">
                    <div class="flex justify-between gap-3"><dt>Status</dt><dd class="font-semibold text-slate-900">{{ $company->gocardless_mandate_status ?: 'Not started' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Mandate ID</dt><dd class="font-mono">{{ $company->gocardless_mandate_id ?: 'Not set' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Customer ID</dt><dd class="font-mono">{{ $company->gocardless_customer_id ?: 'Not set' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Requested</dt><dd>{{ $company->gocardless_mandate_requested_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Confirmed</dt><dd>{{ $company->gocardless_mandate_confirmed_at?->format('d M Y H:i') ?? 'Not confirmed' }}</dd></div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="post" action="{{ route('companies.gocardless.mandate', $company) }}">
                        @csrf
                        <button class="app-button-secondary">{{ $company->gocardless_authorisation_url ? 'Create new mandate link' : 'Create mandate link' }}</button>
                    </form>
                    @if($company->gocardless_billing_request_id)
                        <form method="post" action="{{ route('companies.gocardless.refresh', $company) }}">
                            @csrf
                            <button class="app-button-secondary">Refresh status</button>
                        </form>
                    @endif
                    @if($company->gocardless_authorisation_url && ! $company->gocardless_mandate_id)
                        <a href="{{ $company->gocardless_authorisation_url }}" target="_blank" rel="noopener" class="app-button">Open mandate link</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="app-panel rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Office Users</h2>
                    <p class="mt-1 text-sm text-slate-500">Company admins and office users for this account.</p>
                </div>
                <a href="{{ route('users.index') }}" class="app-button-secondary">View all users</a>
            </div>
            <div class="mt-5 overflow-x-auto">
                <table class="app-table">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th>User</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
                    <tbody>
                    @forelse($officeUsers as $user)
                        <tr>
                            <td><a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('users.show', $user) }}">{{ $user->name }}</a><div class="text-xs text-slate-500">{{ $user->email }}</div></td>
                            <td>{{ str_replace('_', ' ', $user->role) }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                            <td><a class="text-sm font-bold text-teal-700 hover:text-teal-900" href="{{ route('users.edit', $user) }}">Support</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-slate-500">No office users have been created for this account.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Engineers</h2>
            <div class="mt-4 space-y-3">
                @forelse($engineers as $engineer)
                    <a href="{{ route('users.show', $engineer) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 hover:border-teal-300">
                        <span><span class="block font-bold text-slate-950">{{ $engineer->name }}</span><span class="text-sm text-slate-500">{{ $engineer->email }}</span></span>
                        <span class="text-sm text-slate-500">{{ $engineer->last_login_at?->format('d M Y H:i') ?? 'Never logged in' }}</span>
                    </a>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No engineers are linked to this account.</p>
                @endforelse
            </div>
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Email Sources</h2>
            <div class="mt-4 space-y-3">
                @forelse($company->emailSources as $source)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                        <span><span class="block font-bold text-slate-950">{{ $source->name }}</span><span class="text-sm text-slate-500">{{ $source->mailbox_email }}</span></span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $source->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $source->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No company email sources configured.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
