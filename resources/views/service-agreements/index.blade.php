<x-layouts.app title="Service Agreements">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Commercial agreements</div>
            <h1 class="mt-1 text-2xl font-black text-slate-950">Service Agreements</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Create the agreement first, then attach the machines covered by that agreement. Client and site pricing remain fallback defaults only.</p>
        </div>
        <a href="{{ route('service-agreements.create') }}" class="app-button">Add agreement</a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="app-panel p-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Active agreements</div>
            <div class="mt-2 text-2xl font-black text-slate-950">{{ $activeAgreementCount }}</div>
        </div>
        <div class="app-panel p-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Covered machines</div>
            <div class="mt-2 text-2xl font-black text-teal-700">{{ $coveredMachineCount }}</div>
        </div>
        <div class="app-panel p-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Machines without current agreement</div>
            <div class="mt-2 text-2xl font-black text-amber-700">{{ $uncoveredMachineCount }}</div>
        </div>
    </div>

    <section class="app-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th>Agreement</th>
                        <th>Dates</th>
                        <th>Rates</th>
                        <th>Included copies</th>
                        <th>Machines</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agreements as $agreement)
                        <tr>
                            <td>
                                <div class="font-mono text-sm font-black text-slate-950">{{ $agreement->agreement_number }}</div>
                                <div class="text-xs text-slate-500">Created {{ $agreement->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="text-sm text-slate-700">
                                <div>{{ $agreement->starts_on?->format('d M Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $agreement->ends_on ? 'Ends '.$agreement->ends_on->format('d M Y') : 'Open ended' }}</div>
                            </td>
                            <td class="text-sm text-slate-700">
                                <div>B/W {{ number_format((float) $agreement->mono_ppc, 3) }}p</div>
                                <div>Colour {{ number_format((float) $agreement->colour_ppc, 3) }}p</div>
                            </td>
                            <td class="text-sm text-slate-700">
                                <div>{{ number_format((int) $agreement->included_mono_pages) }} B/W</div>
                                <div>{{ number_format((int) $agreement->included_colour_pages) }} colour</div>
                            </td>
                            <td>
                                <div class="font-black text-slate-950">{{ $agreement->machines->count() }}</div>
                                <div class="max-w-72 truncate text-xs text-slate-500">{{ $agreement->machines->take(3)->pluck('machine_name')->join(', ') }}{{ $agreement->machines->count() > 3 ? ' +' . ($agreement->machines->count() - 3) . ' more' : '' }}</div>
                            </td>
                            <td>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $agreement->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $agreement->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('service-agreements.show', $agreement) }}" class="text-sm font-bold text-teal-700 hover:text-teal-900">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-sm text-slate-500">No service agreements yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-5">{{ $agreements->links() }}</div>
</x-layouts.app>
