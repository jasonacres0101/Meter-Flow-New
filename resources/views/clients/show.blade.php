<x-layouts.app :title="$client->name">
    @php
        $isEngineer = auth()->user()->isEngineer();
        $activeMachines = $client->machines->where('is_active', true)->count();
        $siteCount = $client->sites->count();
        $machineCount = $client->machines->count();
        $topSite = $revenue['by_site']->first();
        $topMachine = $revenue['by_machine']->first();
        $clientStatusTone = ($client->is_active ?? true)
            ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
            : 'bg-slate-100 text-slate-700 ring-slate-200';
    @endphp

    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">Client account</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $clientStatusTone }}">{{ ($client->is_active ?? true) ? 'Active' : 'Inactive' }}</span>
                        @if($client->account_reference)
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-200 ring-1 ring-white/15">{{ $client->account_reference }}</span>
                        @endif
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">{{ $client->name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        {{ $siteCount }} sites / {{ $machineCount }} machines / {{ $activeMachines }} active machines{{ $isEngineer ? '' : ' / '.number_format((float) $client->mono_ppc, 3).'p B/W and '.number_format((float) $client->colour_ppc, 3).'p colour default PPC' }}.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    @unless($isEngineer)
                        <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">Edit client</a>
                        <a href="{{ route('sites.create', ['client_id' => $client->id]) }}" class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200">Add site</a>
                    @endunless
                    @foreach([7, 30, 90] as $filter)
                        <a class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-bold transition {{ $days === $filter ? 'border-teal-300 bg-teal-300 text-slate-950' : 'border-white/15 bg-white/10 text-white hover:bg-white/15' }}" href="?days={{ $filter }}">{{ $filter }}d</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 {{ $isEngineer ? 'md:grid-cols-3' : 'md:grid-cols-6' }}">
            @unless($isEngineer)
                <div class="service-metric"><div class="service-label">Revenue</div><div class="service-value">£{{ number_format($revenue['total_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">B/W revenue</div><div class="service-value">£{{ number_format($revenue['mono_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">Colour revenue</div><div class="service-value">£{{ number_format($revenue['colour_revenue'], 2) }}</div></div>
            @endunless
            <div class="service-metric"><div class="service-label">Pages</div><div class="service-value">{{ number_format($revenue['total_pages']) }}</div></div>
            <div class="service-metric"><div class="service-label">Sites</div><div class="service-value">{{ $siteCount }}</div></div>
            <div class="service-metric"><div class="service-label">Machines</div><div class="service-value">{{ $machineCount }}</div></div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Usage reporting</div>
                    <h2 class="mt-1 text-xl font-black text-white">Client Usage</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Mono, colour and total daily movement across this client for the selected date range.</p>
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
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Included pages</div>
                        <div class="mt-1 break-words font-black text-slate-950">{{ number_format($revenue['included_total_pages']) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Chargeable pages</div>
                        <div class="mt-1 break-words font-black text-slate-950">{{ number_format($revenue['chargeable_total_pages']) }}</div>
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
                    <div id="client-usage" class="h-80"></div>
                </div>
            </div>
        </div>
    </section>

    @unless($isEngineer)
        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Commercials</div>
                        <h2 class="mt-1 text-xl font-black text-white">Revenue By Site</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Site-level revenue, included volume and chargeable pages for this client.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                <div class="bg-white p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Top site</div>
                        <div class="mt-2 break-words text-2xl font-black tracking-normal text-slate-950">{{ $topSite['name'] ?? 'No usage' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">£{{ number_format($topSite['revenue'] ?? 0, 2) }}</span>
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">{{ number_format($topSite['total_pages'] ?? 0) }} pages</span>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Default B/W PPC</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ number_format((float) $client->mono_ppc, 3) }}p</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Default colour PPC</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ number_format((float) $client->colour_ppc, 3) }}p</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-950">Site Revenue Summary</h3>
                            <p class="mt-1 text-sm text-slate-500">Scrollable list of sites ranked by revenue in the selected range.</p>
                        </div>
                    </div>

                    <div class="max-h-96 space-y-3 overflow-y-auto rounded-xl bg-slate-50 p-4 pr-2">
                        @forelse($revenue['by_site'] as $siteRevenue)
                            @php($site = $client->sites->firstWhere('id', $siteRevenue['id']))
                            <a href="{{ $site ? route('sites.show', $site) : '#' }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100 transition hover:border-teal-300 hover:shadow-md">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-black text-slate-950">{{ $siteRevenue['name'] }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700">{{ number_format($siteRevenue['total_pages']) }} pages</span>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ number_format($siteRevenue['chargeable_pages']) }} chargeable</span>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left sm:text-right">
                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Revenue</div>
                                        <div class="mt-1 text-sm font-black text-teal-700">£{{ number_format($siteRevenue['revenue'], 2) }}</div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center">
                                <div>
                                    <h3 class="text-lg font-black text-slate-950">No usage in this range</h3>
                                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Revenue by site will appear when parsed meter readings create daily usage.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Fleet ranking</div>
                        <h2 class="mt-1 text-xl font-black text-white">Top Machines By Revenue</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Highest value machines in this client account for the selected date range.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                <div class="bg-white p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Top machine</div>
                        <div class="mt-2 break-words text-2xl font-black tracking-normal text-slate-950">{{ $topMachine['name'] ?? 'No usage' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">£{{ number_format($topMachine['revenue'] ?? 0, 2) }}</span>
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
                            <h3 class="text-base font-black text-slate-950">Machine Revenue Summary</h3>
                            <p class="mt-1 text-sm text-slate-500">Top machines ranked by total revenue and page movement.</p>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-100">
                        <div class="max-h-96 overflow-y-auto">
                            <table class="app-table">
                                <thead>
                                    <tr>
                                        <th>Machine</th>
                                        <th class="text-right">Pages</th>
                                        <th class="text-right">Chargeable</th>
                                        <th class="text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($revenue['by_machine']->take(10) as $machineRevenue)
                                        @php($machine = $client->machines->firstWhere('id', $machineRevenue['id']))
                                        <tr>
                                            <td class="font-bold text-slate-900">
                                                @if($machine)
                                                    <a href="{{ route('machines.show', $machine) }}" class="hover:text-teal-700 hover:underline">{{ $machineRevenue['name'] }}</a>
                                                @else
                                                    {{ $machineRevenue['name'] }}
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format($machineRevenue['total_pages']) }}</td>
                                            <td class="text-right">{{ number_format($machineRevenue['chargeable_pages']) }}</td>
                                            <td class="text-right font-black text-teal-700">£{{ number_format($machineRevenue['revenue'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-sm text-slate-500">No machine usage in this range.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endunless

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usage = {{ Illuminate\Support\Js::from($usage) }};
            new ApexCharts(document.querySelector('#client-usage'), {
                chart: { type: 'line', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                series: [
                    { name: 'Total pages', data: usage.map(row => row.total_usage) },
                    { name: 'B/W pages', data: usage.map(row => row.mono_usage) },
                    { name: 'Colour pages', data: usage.map(row => row.colour_usage) },
                    @unless($isEngineer)
                        { name: 'Revenue', type: 'area', data: {{ Illuminate\Support\Js::from($dailyRevenue) }} },
                    @endunless
                ],
                xaxis: { categories: usage.map(row => row.date) },
                stroke: { curve: 'smooth', width: [3, 2, 2, 3] },
                dataLabels: { enabled: false },
                colors: ['#0f172a', '#0f766e', '#2563eb', '#f59e0b'],
                grid: { borderColor: '#e2e8f0' }
            }).render();
        });
    </script>
</x-layouts.app>
