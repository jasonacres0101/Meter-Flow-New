<x-layouts.app title="Pricing Settings">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Commercial settings</div>
            <h1 class="mt-1 text-2xl font-black">Pence Per Page Pricing</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Set client default black and white and colour pence-per-page rates, then optional fallback overrides for sites or machines. Create service agreements separately and attach machines to them.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('service-agreements.index') }}" class="app-button-secondary">Service agreements</a>
            <a href="{{ route('reports.revenue') }}" class="app-button-secondary">View revenue report</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('pricing-settings.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        @forelse ($clients as $client)
            <section class="app-panel overflow-hidden rounded-xl">
                <div class="border-b border-slate-200 bg-slate-950 px-5 py-5 text-white">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-xl font-black">{{ $client->name }}</h2>
                            <p class="mt-1 text-sm text-slate-300">Client default rates used by every site and machine unless an override is set.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-300">B/W ppc
                                <input name="clients[{{ $client->id }}][mono_ppc]" type="number" min="0" step="0.001" value="{{ old('clients.'.$client->id.'.mono_ppc', $client->mono_ppc) }}" class="mt-1 w-full rounded-lg border-white/20 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                            </label>
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-300">Colour ppc
                                <input name="clients[{{ $client->id }}][colour_ppc]" type="number" min="0" step="0.001" value="{{ old('clients.'.$client->id.'.colour_ppc', $client->colour_ppc) }}" class="mt-1 w-full rounded-lg border-white/20 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                            </label>
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-300">Included B/W
                                <input name="clients[{{ $client->id }}][included_mono_pages]" type="number" min="0" step="1" value="{{ old('clients.'.$client->id.'.included_mono_pages', $client->included_mono_pages) }}" class="mt-1 w-full rounded-lg border-white/20 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                            </label>
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-300">Included colour
                                <input name="clients[{{ $client->id }}][included_colour_pages]" type="number" min="0" step="1" value="{{ old('clients.'.$client->id.'.included_colour_pages', $client->included_colour_pages) }}" class="mt-1 w-full rounded-lg border-white/20 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($client->sites as $site)
                        <div class="p-5">
                            <div class="grid gap-4 lg:grid-cols-[1fr_34rem] lg:items-start">
                                <div>
                                    <div class="text-base font-black text-slate-900">{{ $site->name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">Site override. Leave blank to inherit {{ number_format((float) $client->mono_ppc, 3) }}p B/W, {{ number_format((float) $client->colour_ppc, 3) }}p colour and included copy allowances.</div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-4">
                                    <label class="text-xs font-bold uppercase tracking-wide text-slate-500">B/W override
                                        <input name="sites[{{ $site->id }}][mono_ppc_override]" type="number" min="0" step="0.001" value="{{ old('sites.'.$site->id.'.mono_ppc_override', $site->mono_ppc_override) }}" placeholder="Inherit" class="mt-1 w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Colour override
                                        <input name="sites[{{ $site->id }}][colour_ppc_override]" type="number" min="0" step="0.001" value="{{ old('sites.'.$site->id.'.colour_ppc_override', $site->colour_ppc_override) }}" placeholder="Inherit" class="mt-1 w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Included B/W
                                        <input name="sites[{{ $site->id }}][included_mono_pages_override]" type="number" min="0" step="1" value="{{ old('sites.'.$site->id.'.included_mono_pages_override', $site->included_mono_pages_override) }}" placeholder="Inherit" class="mt-1 w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Included colour
                                        <input name="sites[{{ $site->id }}][included_colour_pages_override]" type="number" min="0" step="1" value="{{ old('sites.'.$site->id.'.included_colour_pages_override', $site->included_colour_pages_override) }}" placeholder="Inherit" class="mt-1 w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                    </label>
                                </div>
                            </div>
                            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                                <table class="app-table">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th>Machine</th>
                                        <th>Serial</th>
                                        <th>B/W override</th>
                                        <th>Colour override</th>
                                        <th>Included B/W</th>
                                        <th>Included colour</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($site->machines as $machine)
                                        <tr>
                                            <td>
                                                <div class="font-bold text-slate-900">{{ $machine->machine_name }}</div>
                                                <div class="text-xs text-slate-500">{{ $machine->location }}</div>
                                            </td>
                                            <td class="font-mono text-xs text-slate-600">{{ $machine->serial_number }}</td>
                                            <td class="min-w-36">
                                                <input name="machines[{{ $machine->id }}][mono_ppc_override]" type="number" min="0" step="0.001" value="{{ old('machines.'.$machine->id.'.mono_ppc_override', $machine->mono_ppc_override) }}" placeholder="Inherit" class="w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                            </td>
                                            <td class="min-w-36">
                                                <input name="machines[{{ $machine->id }}][colour_ppc_override]" type="number" min="0" step="0.001" value="{{ old('machines.'.$machine->id.'.colour_ppc_override', $machine->colour_ppc_override) }}" placeholder="Inherit" class="w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                            </td>
                                            <td class="min-w-36">
                                                <input name="machines[{{ $machine->id }}][included_mono_pages_override]" type="number" min="0" step="1" value="{{ old('machines.'.$machine->id.'.included_mono_pages_override', $machine->included_mono_pages_override) }}" placeholder="Inherit" class="w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                            </td>
                                            <td class="min-w-36">
                                                <input name="machines[{{ $machine->id }}][included_colour_pages_override]" type="number" min="0" step="1" value="{{ old('machines.'.$machine->id.'.included_colour_pages_override', $machine->included_colour_pages_override) }}" placeholder="Inherit" class="w-full rounded-lg border-zinc-300 px-3 py-2 text-sm">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="app-panel rounded-xl p-8 text-center">
                <h2 class="text-lg font-black">No clients yet</h2>
                <p class="mt-2 text-sm text-slate-500">Add clients and machines before configuring pricing.</p>
            </div>
        @endforelse

        @if ($clients->isNotEmpty())
            <div class="sticky bottom-4 flex justify-end">
                <button class="app-button shadow-2xl">Save pricing</button>
            </div>
        @endif
    </form>
</x-layouts.app>
