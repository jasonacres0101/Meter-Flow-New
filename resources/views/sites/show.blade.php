<x-layouts.app :title="$site->name">
    @php
        $isEngineer = auth()->user()->isEngineer();
        $machineCount = $site->machines->count();
        $activeMachines = $site->machines->where('is_active', true)->count();
        $topMachine = $revenue['by_machine']->first();
        $siteStatusTone = ($site->is_active ?? true)
            ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
            : 'bg-slate-100 text-slate-700 ring-slate-200';
        $address = collect([$site->address_line_1, $site->address_line_2, $site->city, $site->postcode])->filter()->join(', ');
    @endphp

    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">{{ $site->client->name }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $siteStatusTone }}">{{ ($site->is_active ?? true) ? 'Active' : 'Inactive' }}</span>
                        @if($site->latitude && $site->longitude)
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-200 ring-1 ring-white/15">Mapped</span>
                        @endif
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">{{ $site->name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        {{ $machineCount }} machines / {{ $activeMachines }} active{{ $isEngineer ? '' : ' / '.($site->mono_ppc_override ? number_format((float) $site->mono_ppc_override, 3).'p B/W override' : 'client B/W default').' / '.($site->colour_ppc_override ? number_format((float) $site->colour_ppc_override, 3).'p colour override' : 'client colour default') }}.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    @unless($isEngineer)
                        <a href="{{ route('sites.edit', $site) }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">Edit site</a>
                        <a href="{{ route('machines.create', ['site_id' => $site->id]) }}" class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200">Add machine</a>
                    @endunless
                    @foreach([7, 30, 90] as $filter)
                        <a class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-bold transition {{ $days === $filter ? 'border-teal-300 bg-teal-300 text-slate-950' : 'border-white/15 bg-white/10 text-white hover:bg-white/15' }}" href="?days={{ $filter }}">{{ $filter }}d</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 {{ $isEngineer ? 'md:grid-cols-2' : 'md:grid-cols-5' }}">
            @unless($isEngineer)
                <div class="service-metric"><div class="service-label">Revenue</div><div class="service-value">£{{ number_format($revenue['total_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">B/W revenue</div><div class="service-value">£{{ number_format($revenue['mono_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">Colour revenue</div><div class="service-value">£{{ number_format($revenue['colour_revenue'], 2) }}</div></div>
            @endunless
            <div class="service-metric"><div class="service-label">Pages</div><div class="service-value">{{ number_format($revenue['total_pages']) }}</div></div>
            <div class="service-metric"><div class="service-label">Machines</div><div class="service-value">{{ $machineCount }}</div></div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Usage reporting</div>
                    <h2 class="mt-1 text-xl font-black text-white">Site Usage</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Mono, colour and revenue trends for the machines installed at this site.</p>
                </div>
                <span class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm">Last {{ $days }} days</span>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-500">Total pages</div>
                    <div class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ number_format($revenue['total_pages']) }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ number_format($revenue['total_mono_pages']) }} B/W</span>
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">{{ number_format($revenue['total_colour_pages']) }} colour</span>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Chargeable pages</div>
                        <div class="mt-1 break-words font-black text-slate-950">{{ number_format($revenue['chargeable_total_pages']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Site address</div>
                        <div class="mt-1 break-words font-black text-slate-950">{{ $address ?: 'Not set' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Usage Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Daily usage is calculated from differences between meter readings.</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm shadow-slate-100">
                    <div id="site-usage" class="h-80"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Installed estate</div>
                    <h2 class="mt-1 text-xl font-black text-white">Site Machines</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Machine activity, revenue and current installation state at this location.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-500">Top machine</div>
                    <div class="mt-2 break-words text-2xl font-black tracking-normal text-slate-950">{{ $topMachine['name'] ?? 'No usage' }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @unless($isEngineer)
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">£{{ number_format($topMachine['revenue'] ?? 0, 2) }}</span>
                        @endunless
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">{{ number_format($topMachine['total_pages'] ?? 0) }} pages</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Active machines</div>
                    <div class="mt-1 break-words font-black text-slate-950">{{ $activeMachines }} of {{ $machineCount }}</div>
                </div>
            </div>

            <div class="bg-white p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Machine Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Scrollable list of machines ranked by activity in the selected range.</p>
                    </div>
                </div>

                <div class="max-h-96 space-y-3 overflow-y-auto rounded-xl bg-slate-50 p-4 pr-2">
                    @forelse($revenue['by_machine'] as $machineRevenue)
                        @php($machine = $site->machines->firstWhere('id', $machineRevenue['id']))
                        <a href="{{ $machine ? route('machines.show', $machine) : '#' }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100 transition hover:border-teal-300 hover:shadow-md">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="font-black text-slate-950">{{ $machineRevenue['name'] }}</div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700">{{ number_format($machineRevenue['total_pages']) }} pages</span>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ number_format($machineRevenue['chargeable_pages']) }} chargeable</span>
                                    </div>
                                    @if($machine)
                                        <div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $machine->manufacturer }} {{ $machine->model }} / {{ $machine->serial_number }}</div>
                                    @endif
                                </div>
                                @unless($isEngineer)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left sm:text-right">
                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Revenue</div>
                                        <div class="mt-1 text-sm font-black text-teal-700">£{{ number_format($machineRevenue['revenue'], 2) }}</div>
                                    </div>
                                @endunless
                            </div>
                        </a>
                    @empty
                        <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">No machine usage</h3>
                                <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Machine usage will appear once parsed readings are available for this site.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usage = {{ Illuminate\Support\Js::from($usage) }};
            new ApexCharts(document.querySelector('#site-usage'), {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                series: [
                    { name: 'B/W pages', data: usage.map(row => row.mono_usage) },
                    { name: 'Colour pages', data: usage.map(row => row.colour_usage) },
                    @unless($isEngineer)
                        { name: 'Revenue', type: 'line', data: {{ Illuminate\Support\Js::from($dailyRevenue) }} },
                    @endunless
                ],
                xaxis: { categories: usage.map(row => row.date) },
                stroke: { width: [0, 0, 3], curve: 'smooth' },
                plotOptions: { bar: { borderRadius: 5, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                colors: ['#0f172a', '#2563eb', '#0f766e'],
                grid: { borderColor: '#e2e8f0' }
            }).render();
        });
    </script>
</x-layouts.app>
