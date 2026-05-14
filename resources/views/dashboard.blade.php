<x-layouts.app title="Dashboard">
    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">Fleet health command centre</span>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200">{{ number_format($reportsToday) }} reports today</span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">Copier reporting, toner alerts and usage trends.</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Monitor daily report coverage, parser failures, low consumables and page volumes across every client site.</p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <a href="{{ route('machines.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200">Add machine</a>
                    <a href="{{ route('machines.index') }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">View fleet</a>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 md:grid-cols-5">
            <div class="service-metric"><div class="service-label">Active machines</div><div class="service-value">{{ number_format($activeMachines) }}</div></div>
            <div class="service-metric"><div class="service-label">Reports today</div><div class="service-value">{{ number_format($reportsToday) }}</div></div>
            <div class="service-metric"><div class="service-label">Missing today</div><div class="service-value">{{ number_format($missingToday->count()) }}</div></div>
            <div class="service-metric"><div class="service-label">Unmatched emails</div><div class="service-value">{{ number_format($unmatchedEmails) }}</div></div>
            <div class="service-metric"><div class="service-label">Failed parses</div><div class="service-value">{{ number_format($failedParses) }}</div></div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:mt-0">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Usage reporting</div>
                        <h2 class="mt-1 text-xl font-black text-white">Usage Overview</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Today compared with month to date.</p>
                    </div>
                    <span class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm">Live</span>
                </div>
            </div>
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm shadow-slate-100">
                    <div id="dashboard-usage" class="h-72"></div>
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:mt-0">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Fleet ranking</div>
                        <h2 class="mt-1 text-xl font-black text-white">Top Machines This Month</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Highest usage devices by pages.</p>
                    </div>
                    <span class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm">Top 10</span>
                </div>
            </div>
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm shadow-slate-100">
                    <div id="top-machines" class="h-72"></div>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div class="text-xs font-black uppercase tracking-wide text-teal-700">Meter activity</div>
                <h2 class="mt-1 text-lg font-black text-slate-950">Latest Meter Readings</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <tbody>
                    @foreach ($latestReadings as $reading)
                        <tr><td class="py-2">{{ $reading->machine->machine_name ?? $reading->machine->serial_number }}</td><td>{{ $reading->reading_date->format('d M H:i') }}</td><td class="text-right">{{ number_format($reading->total_counter) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div class="text-xs font-black uppercase tracking-wide text-teal-700">Consumables</div>
                <h2 class="mt-1 text-lg font-black text-slate-950">Low Toner Alerts</h2>
            </div>
            <div class="max-h-80 space-y-2 overflow-y-auto bg-slate-50 p-5">
                @forelse ($lowTonerAlerts as $toner)
                    <div class="flex items-center justify-between rounded-xl border border-rose-100 bg-white px-3 py-2 text-rose-900 shadow-sm shadow-slate-100"><span>{{ $toner->machine->machine_name ?? $toner->machine->serial_number }} {{ ucfirst($toner->colour) }}</span><span class="font-bold">{{ $toner->percentage }}%</span></div>
                @empty
                    <p class="rounded-xl border border-emerald-100 bg-white px-3 py-4 text-sm font-semibold text-emerald-800">No low toner alerts.</p>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new ApexCharts(document.querySelector('#dashboard-usage'), {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                plotOptions: { bar: { borderRadius: 8, columnWidth: '48%' } },
                series: [{ name: 'Pages', data: [{{ (int) $totalPagesToday }}, {{ (int) $totalPagesMonth }}] }],
                xaxis: { categories: ['Today', 'This month'] },
                grid: { borderColor: '#e2e8f0' },
                colors: ['#0f766e']
            }).render();
            new ApexCharts(document.querySelector('#top-machines'), {
                chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                plotOptions: { bar: { borderRadius: 8, horizontal: true } },
                series: [{ name: 'Pages', data: @json($topMachines->values()) }],
                xaxis: { categories: @json($topMachines->keys()->map(fn ($id) => 'Machine '.$id)->values()) },
                grid: { borderColor: '#e2e8f0' },
                colors: ['#2563eb']
            }).render();
        });
    </script>
</x-layouts.app>
