<x-layouts.app title="Revenue Reports">
    @php
        $query = request()->query();
        $exportUrl = fn (string $format) => route('reports.revenue.export', $format).($query ? '?'.http_build_query($query) : '');
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-blue-700">Report generator</div>
            <h1 class="mt-1 text-2xl font-black">Detailed PPC Revenue Reports</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Generate client, site or machine reports using custom dates or monthly, quarterly and yearly presets. Revenue uses daily counter movement and the effective PPC rate.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $exportUrl('pdf') }}" class="app-button-secondary">Export PDF</a>
            <a href="{{ $exportUrl('csv') }}" class="app-button-secondary">Export CSV</a>
            <a href="{{ $exportUrl('excel') }}" class="app-button">Export Excel</a>
        </div>
    </div>

    <form method="get" action="{{ route('reports.revenue') }}" class="app-panel mb-6 rounded-xl p-5">
        <div class="grid gap-4 lg:grid-cols-5">
            <label class="text-sm font-semibold text-slate-700">Report period
                <select name="period" id="period-select" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    @foreach($periods as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">From
                <input name="from" type="date" value="{{ $from->toDateString() }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">To
                <input name="to" type="date" value="{{ $to->toDateString() }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Report level
                <select name="scope" id="scope-select" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    <option value="all" @selected($scope === 'all')>All clients</option>
                    <option value="client" @selected($scope === 'client')>Client</option>
                    <option value="site" @selected($scope === 'site')>Site</option>
                    <option value="machine" @selected($scope === 'machine')>Machine</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Client, site or machine
                <select name="scope_id" id="scope-id-select" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    <option value="">All available</option>
                    @foreach($clients as $client)
                        <option data-scope="client" value="{{ $client->id }}" @selected($scope === 'client' && $scope_id === $client->id)>{{ $client->name }}</option>
                    @endforeach
                    @foreach($sites as $site)
                        <option data-scope="site" value="{{ $site->id }}" @selected($scope === 'site' && $scope_id === $site->id)>{{ $site->client->name }} / {{ $site->name }}</option>
                    @endforeach
                    @foreach($machines as $machine)
                        <option data-scope="machine" value="{{ $machine->id }}" @selected($scope === 'machine' && $scope_id === $machine->id)>{{ $machine->machine_name }} / {{ $machine->serial_number }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-500"><span class="font-bold text-slate-800">{{ $scope_label }}</span> / {{ $period_label }} / {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
            <button class="app-button">Generate report</button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <x-stat label="Total revenue" value="£{{ number_format($summary['total_revenue'], 2) }}" tone="teal" />
        <x-stat label="B/W revenue" value="£{{ number_format($summary['mono_revenue'], 2) }}" tone="slate" />
        <x-stat label="Colour revenue" value="£{{ number_format($summary['colour_revenue'], 2) }}" tone="blue" />
        <x-stat label="Total pages" :value="number_format($summary['total_pages'])" tone="amber" />
        <x-stat label="Included pages" :value="number_format($summary['included_total_pages'])" tone="slate" />
        <x-stat label="Chargeable pages" :value="number_format($summary['chargeable_total_pages'])" tone="blue" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <section class="app-panel rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black">Revenue and Page Trend</h2>
                    <p class="text-sm text-slate-500">Daily totals for the selected report scope.</p>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $daily->count() }} days</span>
            </div>
            <div id="report-trend" class="mt-4 h-80"></div>
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Report Summary</h2>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Scope</dt><dd class="mt-1 font-black text-slate-950">{{ $scope_label }}</dd></div>
                <div class="rounded-lg bg-blue-50 p-3"><dt class="font-bold text-blue-700">Period</dt><dd class="mt-1 font-black text-blue-950">{{ $period_label }}</dd></div>
                <div class="rounded-lg bg-teal-50 p-3"><dt class="font-bold text-teal-700">Average daily revenue</dt><dd class="mt-1 font-black text-teal-950">£{{ number_format($daily->count() ? $summary['total_revenue'] / $daily->count() : 0, 2) }}</dd></div>
                <div class="rounded-lg bg-amber-50 p-3"><dt class="font-bold text-amber-700">Average daily pages</dt><dd class="mt-1 font-black text-amber-950">{{ number_format($daily->count() ? $summary['total_pages'] / $daily->count() : 0) }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Included allowance used</dt><dd class="mt-1 font-black text-slate-950">{{ number_format($summary['included_total_pages']) }}</dd></div>
                <div class="rounded-lg bg-blue-50 p-3"><dt class="font-bold text-blue-700">Chargeable pages</dt><dd class="mt-1 font-black text-blue-950">{{ number_format($summary['chargeable_total_pages']) }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        @foreach ([
            'by_client' => 'Revenue By Client',
            'by_site' => 'Revenue By Site',
            'by_machine' => 'Revenue By Machine',
        ] as $key => $title)
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">{{ $title }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="app-table">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th>Name</th><th class="text-right">Total</th><th class="text-right">Included</th><th class="text-right">Chargeable</th><th class="text-right">Revenue</th></tr></thead>
                        <tbody>
                        @forelse ($summary[$key] as $row)
                            <tr><td class="font-bold text-slate-900">{{ $row['name'] }}</td><td class="text-right">{{ number_format($row['total_pages']) }}</td><td class="text-right">{{ number_format($row['included_pages']) }}</td><td class="text-right">{{ number_format($row['chargeable_pages']) }}</td><td class="text-right font-black text-teal-700">£{{ number_format($row['revenue'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-slate-500">No usage in this date range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <section class="app-panel mt-6 rounded-xl p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-lg font-black">Detailed Reading Revenue</h2><p class="text-sm text-slate-500">Each row is calculated from the difference to the previous meter reading.</p></div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ number_format($detailRows->total()) }} rows</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th>Date</th><th>Client</th><th>Site</th><th>Machine</th><th>Agreement</th><th class="text-right">B/W</th><th class="text-right">Colour</th><th class="text-right">Included</th><th class="text-right">Chargeable</th><th class="text-right">B/W PPC</th><th class="text-right">Colour PPC</th><th class="text-right">Revenue</th>
                </tr>
                </thead>
                <tbody>
                @forelse($detailRows as $row)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td>{{ $row['client_name'] }}</td>
                        <td>{{ $row['site_name'] }}</td>
                        <td class="font-bold text-slate-900">{{ $row['machine_name'] }}</td>
                        <td><span class="font-mono text-xs">{{ $row['service_agreement_number'] ?? 'Legacy pricing' }}</span></td>
                        <td class="text-right">{{ number_format((int) $row['mono_usage']) }}</td>
                        <td class="text-right">{{ number_format((int) $row['colour_usage']) }}</td>
                        <td class="text-right">{{ number_format((int) $row['included_total_pages']) }}</td>
                        <td class="text-right">{{ number_format((int) $row['chargeable_total_pages']) }}</td>
                        <td class="text-right">{{ number_format((float) $row['mono_ppc'], 3) }}p</td>
                        <td class="text-right">{{ number_format((float) $row['colour_ppc'], 3) }}p</td>
                        <td class="text-right font-black text-teal-700">£{{ number_format((float) $row['total_revenue'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center text-slate-500">No readings found for this report.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($detailRows->hasPages())
            <div class="mt-4 border-t border-slate-100 pt-4">
                {{ $detailRows->links() }}
            </div>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scopeSelect = document.querySelector('#scope-select');
            const scopeIdSelect = document.querySelector('#scope-id-select');
            const filterOptions = () => {
                const selectedScope = scopeSelect.value;
                Array.from(scopeIdSelect.options).forEach((option) => {
                    option.hidden = option.value !== '' && option.dataset.scope !== selectedScope;
                });
                if (scopeIdSelect.selectedOptions[0]?.hidden) {
                    scopeIdSelect.value = '';
                }
            };
            scopeSelect.addEventListener('change', filterOptions);
            filterOptions();

            const daily = {{ Illuminate\Support\Js::from($daily) }};
            new ApexCharts(document.querySelector('#report-trend'), {
                chart: { type: 'line', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                series: [
                    { name: 'Revenue', type: 'area', data: daily.map(row => row.revenue) },
                    { name: 'Chargeable pages', data: daily.map(row => row.chargeable_pages) },
                    { name: 'Included pages', data: daily.map(row => row.included_pages) },
                ],
                xaxis: { categories: daily.map(row => row.date), labels: { rotate: -45 } },
                yaxis: [{ labels: { formatter: value => '£' + Number(value).toFixed(0) } }],
                stroke: { curve: 'smooth', width: [3, 2, 2] },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
                colors: ['#0f766e', '#0f172a', '#2563eb'],
                grid: { borderColor: '#e2e8f0' },
                dataLabels: { enabled: false },
            }).render();
        });
    </script>
</x-layouts.app>
