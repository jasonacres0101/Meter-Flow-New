<x-layouts.app :title="$machine->serial_number">
    @php
        $openTicketCount = $machine->serviceTickets->whereNotIn('status', ['resolved', 'closed'])->count();
        $lastReport = $machine->incomingReportEmails->first();
        $hasUsageData = $usage->isNotEmpty();
        $hasTonerData = $tonerValues->filter(fn ($value) => $value !== null)->isNotEmpty();
        $availableTonerValues = $tonerValues->filter(fn ($value) => $value !== null);
        $insertedTonerCollection = collect($insertedTonerNumbers ?? [])->filter(fn ($value) => $value !== null && $value !== '');
        $hasInsertedTonerData = $insertedTonerCollection->isNotEmpty();
        $lowestTonerColour = $availableTonerValues->isNotEmpty() ? $availableTonerValues->sort()->keys()->first() : null;
        $lowestTonerValue = $lowestTonerColour ? $availableTonerValues->get($lowestTonerColour) : null;
        $serviceTickets = $machine->serviceTickets->sortByDesc('created_at');
        $resolvedTicketCount = $machine->serviceTickets->whereIn('status', ['resolved', 'closed'])->count();
        $latestServiceTicket = $serviceTickets->first();
        $machineStatusTone = $machine->is_active
            ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
            : 'bg-slate-100 text-slate-700 ring-slate-200';
        $reportStatusTone = $lastReport
            ? 'bg-teal-100 text-teal-800 ring-teal-200'
            : 'bg-amber-100 text-amber-900 ring-amber-200';
        $networkItems = [
            'MAC address' => $machine->mac_address,
            'Subnet mask' => $machine->subnet_mask,
            'Gateway' => $machine->gateway,
            'Primary DNS' => $machine->primary_dns,
            'Secondary DNS' => $machine->secondary_dns,
            'VLAN' => $machine->network_vlan,
            'SNMP version' => $machine->snmp_version,
            'SNMP community' => $machine->snmp_community,
        ];
    @endphp

    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">{{ $machine->serial_number }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $machineStatusTone }}">{{ $machine->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $reportStatusTone }}">{{ $lastReport ? 'Reporting' : 'No report' }}</span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">{{ $machine->machine_name ?? $machine->model }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        {{ $machine->client->name }} / {{ $machine->site->name }} / {{ $machine->manufacturer }} {{ $machine->model }} / {{ $machine->location ?? 'No location' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    @if($machine->ip_address)
                        <a
                            href="http://{{ $machine->ip_address }}"
                            target="_blank"
                            rel="noopener"
                            onclick="return confirm('Make sure you are on the same network as this printer/copier before continuing.');"
                            class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200"
                        >
                            Open device web panel
                        </a>
                    @else
                        <span class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-slate-300">
                            No IP address
                        </span>
                    @endif
                    @unless(auth()->user()->isEngineer())
                        <a href="{{ route('machines.edit', $machine) }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">Edit machine</a>
                    @endunless
                    @foreach([7, 30, 90] as $filter)
                        <a class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-bold transition {{ $days === $filter ? 'border-teal-300 bg-teal-300 text-slate-950' : 'border-white/15 bg-white/10 text-white hover:bg-white/15' }}" href="?days={{ $filter }}">{{ $filter }}d</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 md:grid-cols-4">
            <div class="service-metric"><div class="service-label">Client</div><div class="service-value">{{ $machine->client->name }}</div></div>
            <div class="service-metric"><div class="service-label">Site</div><div class="service-value">{{ $machine->site->name }}</div></div>
            <div class="service-metric"><div class="service-label">Machine</div><div class="service-value">{{ $machine->manufacturer }} {{ $machine->model }}</div></div>
            <div class="service-metric"><div class="service-label">Last report</div><div class="service-value">{{ $lastReport?->received_at?->format('d M H:i') ?? 'None' }}</div></div>
        </div>
    </section>

    <section class="service-panel mt-6">
        <div class="service-header-dark">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="service-eyebrow">Machine overview</div>
                    <h2 class="service-title">Machine Details and Update</h2>
                </div>
                <div class="text-sm font-bold text-slate-300">Added {{ $machine->created_at?->format('d M Y') ?? 'Unknown' }}</div>
            </div>
        </div>

        <div class="service-split">
            <div class="service-pane">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Device Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Core identity fields used for matching reports and supporting the machine.</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $machineStatusTone }}">{{ $machine->is_active ? 'Active' : 'Inactive' }}</span>
                </div>

                <div class="mt-4 grid gap-3">
                    <div class="service-card">
                        <div class="service-label">Serial number</div>
                        <div class="service-value font-mono text-lg">{{ $machine->serial_number }}</div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="service-card-white">
                            <div class="service-label">Machine name</div>
                            <div class="service-value">{{ $machine->machine_name ?: 'Not set' }}</div>
                        </div>
                        <div class="service-card-white">
                            <div class="service-label">Location</div>
                            <div class="service-value">{{ $machine->location ?: 'Not set' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="service-pane">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Update Machine</h3>
                        <p class="mt-1 text-sm text-slate-500">Review setup details or open the edit form for this device.</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $reportStatusTone }}">{{ $lastReport ? 'Email received' : 'No email yet' }}</span>
                </div>

                @unless(auth()->user()->isEngineer())
                    <div class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-black text-slate-950">Machine setup</div>
                                <p class="mt-1 text-sm text-slate-600">Edit model, serial, reporting email, network settings and credentials from the machine setup form.</p>
                            </div>
                            <a href="{{ route('machines.edit', $machine) }}" class="app-button-secondary">Edit machine</a>
                        </div>
                    </div>
                @endunless

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach([
                        'Client' => [$machine->client->name, $machine->site->name],
                        'Model' => [$machine->manufacturer, $machine->model],
                        'Report sender' => [$machine->expected_report_sender_email ?: 'Not set', 'Expected daily email source'],
                        'Last report' => [$lastReport?->received_at?->format('d M Y H:i') ?? 'Waiting', $lastReport?->subject ?? 'No stored report subject'],
                    ] as $label => [$value, $meta])
                        <div class="service-card">
                            <div class="service-label">{{ $label }}</div>
                            <div class="service-value">{{ $value }}</div>
                            <div class="service-meta">{{ $meta }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="service-panel mt-6">
        <div class="service-header-light">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="service-eyebrow">Performance summary</div>
                    <h2 class="service-title">Counters and Commercials</h2>
                </div>
                <div class="text-sm font-bold text-slate-500">Last {{ $days }} days</div>
            </div>
        </div>
        <div class="service-metric-strip">
            @unless(auth()->user()->isEngineer())
                <div class="service-metric"><div class="service-label">Revenue</div><div class="service-value-lg">£{{ number_format($revenue['total_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">B/W revenue</div><div class="service-value-lg">£{{ number_format($revenue['mono_revenue'], 2) }}</div></div>
                <div class="service-metric"><div class="service-label">Colour revenue</div><div class="service-value-lg">£{{ number_format($revenue['colour_revenue'], 2) }}</div></div>
            @endunless
            <div class="service-metric"><div class="service-label">Pages</div><div class="service-value-lg">{{ number_format($revenue['total_pages']) }}</div></div>
            <div class="service-metric"><div class="service-label">Total counter</div><div class="service-value-lg">{{ $latestReading?->total_counter ? number_format($latestReading->total_counter) : 'Unknown' }}</div></div>
            <div class="service-metric"><div class="service-label">Open tickets</div><div class="service-value-lg">{{ $openTicketCount }}</div></div>
        </div>
    </section>

        <section class="service-panel mt-6">
            <div class="service-header-dark">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="service-eyebrow">Usage analysis</div>
                        <h2 class="service-title">Daily Usage</h2>
                        <p class="service-subtitle">Calculated from the difference between consecutive meter readings.</p>
                    </div>
                    <span class="rounded-full {{ $hasUsageData ? 'bg-teal-300/20 text-teal-100 ring-teal-300/30' : 'bg-amber-300/20 text-amber-100 ring-amber-300/30' }} px-3 py-1 text-xs font-black uppercase tracking-wide ring-1">
                        {{ $hasUsageData ? 'Last '.$days.' days' : 'Waiting for readings' }}
                    </span>
                </div>
            </div>
            <div class="p-5">
                @if($hasUsageData)
                    <div id="machine-usage" class="h-80"></div>
                @else
                    <div class="grid min-h-80 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 lg:grid-cols-[0.8fr_1.2fr]">
                        <div class="service-pane-dark">
                            <div class="service-eyebrow text-amber-200">Usage pending</div>
                            <div class="mt-2 text-2xl font-black">No daily usage yet</div>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Daily usage needs at least two meter readings so the system can calculate the difference between them.</p>
                        </div>
                        <div class="service-pane">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="service-card">
                                    <div class="service-label">Latest counter</div>
                                    <div class="service-value-lg">{{ $latestReading?->total_counter ? number_format($latestReading->total_counter) : 'Unknown' }}</div>
                                </div>
                                <div class="service-card">
                                    <div class="service-label">Reading date</div>
                                    <div class="service-value-lg">{{ $latestReading?->reading_date?->format('d M') ?? 'None' }}</div>
                                </div>
                                <div class="service-card">
                                    <div class="service-label">Reports stored</div>
                                    <div class="service-value-lg">{{ $machine->incomingReportEmails->count() }}</div>
                                </div>
                            </div>
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                                The machine has a stored email, but no meter reading history yet. Reprocess the email once the correct parser/template is available, or wait for the next matched report.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Connection details</div>
                        <h2 class="mt-1 text-xl font-black text-white">Network Settings</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Use these details while onsite or connected to the customer network.</p>
                    </div>
                    @if($machine->ip_address)
                        <a
                            href="http://{{ $machine->ip_address }}"
                            target="_blank"
                            rel="noopener"
                            onclick="return confirm('Make sure you are on the same network as this printer/copier before continuing.');"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-50"
                        >
                            Open web panel
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                <div class="bg-white p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Primary address</div>
                        <div class="mt-2 break-words font-mono text-3xl font-black tracking-normal text-slate-950">{{ $machine->ip_address ?: 'Not set' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ $machine->dhcp_enabled ? 'DHCP enabled' : 'Static IP' }}</span>
                            @if($machine->network_vlan)
                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">VLAN {{ $machine->network_vlan }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Hostname</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $machine->hostname ?: 'Not set' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Gateway</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $machine->gateway ?: 'Not set' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-950">Connection Summary</h3>
                            <p class="mt-1 text-sm text-slate-500">Network values the engineer may need for diagnosis and web admin access.</p>
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach([
                            'MAC address' => $machine->mac_address,
                            'Subnet mask' => $machine->subnet_mask,
                            'Gateway' => $machine->gateway,
                            'Primary DNS' => $machine->primary_dns,
                            'Secondary DNS' => $machine->secondary_dns,
                            'VLAN' => $machine->network_vlan,
                            'SNMP version' => $machine->snmp_version,
                            'SNMP community' => $machine->snmp_community,
                        ] as $label => $value)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 shadow-sm shadow-slate-100">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                                <div class="mt-1 break-words font-black text-slate-950">{{ filled($value) ? $value : 'Not set' }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($machine->network_notes)
                        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-950">
                            <div class="font-black">Network notes</div>
                            <p class="mt-1 whitespace-pre-line">{{ $machine->network_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Consumables</div>
                        <h2 class="mt-1 text-xl font-black text-white">Toner Levels</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Latest residual percentage, cartridge lifecycle data and consumable risk from parsed reports.</p>
                    </div>
                    @if($hasInsertedTonerData)
                        <span class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm">Lifecycle data</span>
                    @endif
                </div>
            </div>

            <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                <div class="bg-white p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Consumable health</div>
                        <div class="mt-2 text-3xl font-black tracking-normal text-slate-950">
                            {{ $lowestTonerValue !== null ? $lowestTonerValue.'%' : 'No data' }}
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">
                                {{ $lowestTonerColour ? ucfirst($lowestTonerColour).' lowest' : 'Awaiting report' }}
                            </span>
                            @if($hasTonerData)
                                <span class="rounded-full {{ $lowestTonerValue !== null && $lowestTonerValue <= 15 ? 'bg-rose-50 text-rose-800 ring-rose-200' : 'bg-teal-50 text-teal-800 ring-teal-200' }} px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1">
                                    {{ $lowestTonerValue !== null && $lowestTonerValue <= 15 ? 'Action needed' : 'Within range' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        @foreach(['black' => 'Black', 'cyan' => 'Cyan', 'magenta' => 'Magenta', 'yellow' => 'Yellow'] as $colour => $label)
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                                        <div class="mt-1 font-black text-slate-950">{{ $tonerValues->get($colour) !== null ? $tonerValues->get($colour).'%' : 'Not set' }}</div>
                                    </div>
                                    <span class="h-8 w-8 rounded-lg border border-slate-200 {{ $colour === 'black' ? 'bg-slate-950' : ($colour === 'cyan' ? 'bg-cyan-400' : ($colour === 'magenta' ? 'bg-fuchsia-500' : 'bg-yellow-300')) }}"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-950">Toner Summary</h3>
                            <p class="mt-1 text-sm text-slate-500">Current toner bars and inserted cartridge count from parsed consumable readings.</p>
                        </div>
                    </div>

                    @if($hasTonerData)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-sm shadow-slate-100">
                            <div id="toner-levels" class="h-72"></div>
                        </div>
                    @else
                        <div class="flex h-72 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                            <div>
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-white text-lg font-black text-slate-400 shadow-sm">%</div>
                                <h3 class="mt-4 text-lg font-black text-slate-950">No toner readings yet</h3>
                                <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Toner bars will appear after an incoming report is parsed with consumable percentages.</p>
                            </div>
                        </div>
                    @endif

                    @if($hasInsertedTonerData)
                        <div class="mt-4">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-black text-slate-950">Inserted toner numbers</h4>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Last reported {{ $insertedTonerReport?->received_at?->format('d M Y H:i') ?? 'from parsed email' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                @foreach(['black' => 'Black', 'cyan' => 'Cyan', 'magenta' => 'Magenta', 'yellow' => 'Yellow'] as $colour => $label)
                                    @if(array_key_exists($colour, $insertedTonerNumbers) && $insertedTonerNumbers[$colour] !== null && $insertedTonerNumbers[$colour] !== '')
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 shadow-sm shadow-slate-100">
                                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }} inserted</div>
                                            <div class="mt-1 break-words font-black text-slate-950">{{ $insertedTonerNumbers[$colour] }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-500">
                            Inserted toner numbers were not included in the latest parsed reports for this machine.
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Service desk</div>
                        <h2 class="mt-1 text-xl font-black text-white">Service Tickets</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Recent repair and maintenance activity for this machine.</p>
                    </div>
                    @unless(auth()->user()->isEngineer())
                        <a href="{{ route('service-tickets.create', ['machine_id' => $machine->id]) }}" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-50">New ticket</a>
                    @endunless
                </div>
            </div>

            <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                <div class="bg-white p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Open tickets</div>
                        <div class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $openTicketCount }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ $resolvedTicketCount }} resolved</span>
                            @if($latestServiceTicket)
                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">Latest {{ $latestServiceTicket->created_at->format('d M') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Assigned engineer</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $latestServiceTicket?->assignedEngineer?->name ?? 'Unassigned' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Latest status</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $latestServiceTicket ? str_replace('_', ' ', $latestServiceTicket->status) : 'No tickets' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-950">Ticket Summary</h3>
                            <p class="mt-1 text-sm text-slate-500">Scrollable list of recent jobs, assignees and current ticket states.</p>
                        </div>
                    </div>

                    <div class="max-h-96 space-y-3 overflow-y-auto rounded-xl bg-slate-50 p-4 pr-2">
                        @forelse($serviceTickets as $ticket)
                            <a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100 transition hover:border-teal-300 hover:shadow-md" href="{{ route('service-tickets.show', $ticket) }}">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="font-black text-slate-950">{{ $ticket->ticket_number }}</div>
                                            <span class="rounded-full {{ in_array($ticket->status, ['resolved', 'closed'], true) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }} px-2.5 py-1 text-xs font-black uppercase tracking-wide">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-slate-700">{{ $ticket->title }}</div>
                                        <div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">Opened {{ $ticket->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left sm:text-right">
                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Engineer</div>
                                        <div class="mt-1 text-sm font-black text-slate-950">{{ $ticket->assignedEngineer?->name ?? 'Unassigned' }}</div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center">
                                <div>
                                    <h3 class="text-lg font-black text-slate-950">No service tickets</h3>
                                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">New repair or maintenance activity will appear here once a ticket is raised.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

    @unless(auth()->user()->isEngineer())
        <section class="service-panel mt-6">
            <div class="service-header-dark">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="service-eyebrow text-amber-200">Secure storage</div>
                        <h2 class="service-title">Encrypted Credential Vault</h2>
                        <p class="service-subtitle">Passwords and notes are encrypted at rest using the Laravel app key.</p>
                    </div>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-200 ring-1 ring-white/20">{{ $machine->credentials->count() }} saved</span>
                </div>
            </div>
            <div class="service-split">
                <div class="max-h-[34rem] space-y-3 overflow-y-auto bg-slate-50 p-5">
                    @forelse($machine->credentials as $credential)
                        <div class="service-card-white">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Credential</div>
                                    <div class="mt-1 font-black text-slate-950">{{ $credential->label }}</div>
                                    <div class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                        <div><span class="font-bold text-slate-800">Username:</span> {{ $credential->username ?: 'No username' }}</div>
                                        <div><span class="font-bold text-slate-800">URL:</span> {{ $credential->url ?: 'No URL saved' }}</div>
                                    </div>
                                    <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-sm text-white">{{ $credential->password ?: 'No password stored' }}</div>
                                    @if($credential->notes)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $credential->notes }}</p>@endif
                                </div>
                                <form method="post" action="{{ route('machines.credentials.destroy', [$machine, $credential]) }}">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700">Delete</button></form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg bg-white p-4 text-sm text-slate-500">No credentials saved for this machine.</p>
                    @endforelse
                </div>
                <form method="post" action="{{ route('machines.credentials.store', $machine) }}" class="service-pane">
                    @csrf
                    <h3 class="text-lg font-black text-slate-950">Add credential</h3>
                    <p class="mt-1 text-sm text-slate-500">Save web admin, service or network credentials for authorised office users.</p>
                    <div class="mt-4 grid gap-3">
                        <label class="app-field">Label<input name="label" placeholder="Web admin" class="app-field-control"></label>
                        <label class="app-field">Username<input name="username" placeholder="Username" class="app-field-control"></label>
                        <label class="app-field">Password<input name="password" type="password" placeholder="Password" class="app-field-control"></label>
                        <label class="app-field">URL<input name="url" placeholder="https://device-admin.local" class="app-field-control"></label>
                        <label class="app-field">Last rotated<input name="last_rotated_at" type="date" class="app-field-control"></label>
                        <label class="app-field">Secure notes<textarea name="notes" placeholder="Secure notes" class="app-field-control h-24"></textarea></label>
                    </div>
                    <button class="app-button mt-4">Save encrypted credential</button>
                </form>
            </div>
        </section>

        <section class="service-panel mt-6">
            <div class="service-header-light">
                <div class="service-eyebrow text-slate-500">Stored reports</div>
                <h2 class="service-title">Raw Emails</h2>
            </div>
            <div class="service-scroll-list space-y-3">
                @forelse($machine->incomingReportEmails as $email)
                    <a class="block rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm shadow-slate-100 transition hover:border-teal-300" href="{{ route('incoming-report-emails.show', $email) }}">
                        <span class="font-black text-slate-950">{{ $email->received_at->format('d M H:i') }}</span>
                        <span class="ml-2 text-slate-700">{{ $email->subject }}</span>
                    </a>
                @empty
                    <p class="rounded-lg bg-white p-4 text-sm text-slate-500">No raw emails are linked to this machine yet.</p>
                @endforelse
            </div>
        </section>
    @endunless

    @if($parseErrors->isNotEmpty())
        <section class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <h2 class="text-lg font-black">Parse Errors</h2>
            @foreach($parseErrors as $error)
                <p class="mt-2">{{ $error->parse_error }}</p>
            @endforeach
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usage = {{ Illuminate\Support\Js::from($usage) }};
            const usageElement = document.querySelector('#machine-usage');
            if (usageElement) {
                new ApexCharts(usageElement, {
                    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                    series: [
                        { name: 'B/W pages', data: usage.map(row => row.mono_usage) },
                        { name: 'Colour pages', data: usage.map(row => row.colour_usage) },
                        @unless(auth()->user()->isEngineer())
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
            }

            const tonerElement = document.querySelector('#toner-levels');
            if (tonerElement) {
                new ApexCharts(tonerElement, {
                    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Instrument Sans, sans-serif' },
                    plotOptions: { bar: { borderRadius: 6, distributed: true } },
                    series: [{ name: 'Percent', data: {{ Illuminate\Support\Js::from($tonerValues->values()) }} }],
                    xaxis: { categories: {{ Illuminate\Support\Js::from($tonerLabels) }} },
                    yaxis: { max: 100 },
                    colors: ['#0f172a', '#06b6d4', '#db2777', '#f59e0b']
                }).render();
            }
        });
    </script>
</x-layouts.app>
