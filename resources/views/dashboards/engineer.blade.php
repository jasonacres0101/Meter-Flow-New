<x-layouts.app title="Engineer Dashboard">
    @php
        $nextTicket = $assignedTickets->first();
        $topArea = $areaCounts->sortDesc()->keys()->first();
        $topAreaCount = $topArea ? $areaCounts->get($topArea) : 0;
    @endphp

    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">Service workload</span>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-amber-900 ring-1 ring-amber-200">{{ number_format($waitingAcceptanceCount) }} waiting acceptance</span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">Engineer Dashboard</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Pick up available repair work by area, then manage accepted jobs, scheduled visits, resolutions and photos from your service queue.</p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <a href="{{ route('service-tickets.index') }}" class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200">View tickets</a>
                    <a href="{{ route('machines.index') }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">View machines</a>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 md:grid-cols-4">
            <div class="service-metric"><div class="service-label">Waiting acceptance</div><div class="service-value">{{ number_format($waitingAcceptanceCount) }}</div></div>
            <div class="service-metric"><div class="service-label">Open tickets</div><div class="service-value">{{ number_format($openTicketCount) }}</div></div>
            <div class="service-metric"><div class="service-label">Machines open</div><div class="service-value">{{ number_format($openMachineCount) }}</div></div>
            <div class="service-metric"><div class="service-label">Closed tickets</div><div class="service-value">{{ number_format($closedTicketCount) }}</div></div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Ticket queue</div>
                    <h2 class="mt-1 text-xl font-black text-white">New Tickets Waiting Acceptance</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Only the town is shown before acceptance so you can choose work by area.</p>
                </div>
                <span class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-black text-slate-950 shadow-sm">{{ number_format($waitingAcceptanceCount) }} available</span>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-500">Busiest area</div>
                    <div class="mt-2 break-words text-3xl font-black tracking-normal text-slate-950">{{ $topArea ?: 'No area' }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ number_format($topAreaCount) }} ticket{{ $topAreaCount === 1 ? '' : 's' }}</span>
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">{{ $areaCounts->count() }} areas</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Available work by area</div>
                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                        @forelse($areaCounts as $area => $total)
                            <a href="{{ route('service-tickets.index', ['area' => $area]) }}" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm transition hover:border-teal-300 hover:bg-teal-50">
                                <span class="font-bold text-slate-800">{{ $area }}</span>
                                <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-black text-amber-700">{{ $total }}</span>
                            </a>
                        @empty
                            <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No available ticket areas right now.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Acceptance Queue</h3>
                        <p class="mt-1 text-sm text-slate-500">Manufacturer, model and required skills are visible before accepting the job.</p>
                    </div>
                </div>

                <div class="max-h-[34rem] space-y-3 overflow-y-auto rounded-xl bg-slate-50 p-4 pr-2">
                    @forelse($waitingAcceptance as $ticket)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('service-tickets.show', $ticket) }}" class="font-black text-slate-950 hover:text-teal-700 hover:underline">{{ $ticket->ticket_number }}</a>
                                        <span class="rounded-full {{ $ticket->priority === 'urgent' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900' }} px-2.5 py-1 text-xs font-black uppercase tracking-wide">{{ ucfirst($ticket->priority) }}</span>
                                    </div>
                                    <h3 class="mt-1 font-bold text-slate-900">{{ $ticket->title }}</h3>
                                    <div class="mt-2 grid gap-2 text-sm text-slate-600 md:grid-cols-2">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"><span class="font-bold text-slate-500">Town:</span> {{ $ticket->site?->city ?: 'Unknown' }}</div>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"><span class="font-bold text-slate-500">Machine:</span> {{ $ticket->machine?->manufacturer ?: 'Unknown' }} {{ $ticket->machine?->model }}</div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                                        @foreach([
                                            'required_networking_level' => 'Network',
                                            'required_vlan_level' => 'VLAN',
                                            'required_dhcp_static_ip_level' => 'IP',
                                            'required_dns_level' => 'DNS',
                                            'required_routing_level' => 'Route',
                                            'required_firewall_level' => 'Firewall',
                                        ] as $field => $label)
                                            @if(($ticket->{$field} ?? 'none') !== 'none')
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-black uppercase tracking-wide text-slate-700">{{ $label }}: {{ ucfirst($ticket->{$field}) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('service-tickets.show', $ticket) }}" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-800 transition hover:border-teal-300 hover:text-teal-700">View</a>
                                    <form method="post" action="{{ route('service-tickets.accept', $ticket) }}">
                                        @csrf
                                        <button class="inline-flex items-center justify-center rounded-md bg-teal-600 px-3 py-2 text-sm font-black text-white shadow-sm transition hover:bg-teal-700">Accept</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center">
                            <div>
                                <h3 class="text-lg font-black text-slate-950">No tickets waiting</h3>
                                <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Available jobs offered to you will appear here before assignment.</p>
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
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Assigned work</div>
                    <h2 class="mt-1 text-xl font-black text-white">My Active Tickets</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Jobs assigned to you that still need attendance, updates or completion.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
            <div class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-500">Next assigned job</div>
                    <div class="mt-2 break-words text-2xl font-black tracking-normal text-slate-950">{{ $nextTicket?->ticket_number ?? 'No active jobs' }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ $nextTicket?->site?->city ?: 'No town' }}</span>
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">{{ $nextTicket?->scheduled_for?->format('d M H:i') ?? 'Not scheduled' }}</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Open machines</div>
                    <div class="mt-1 break-words font-black text-slate-950">{{ number_format($openMachineCount) }}</div>
                </div>
            </div>

            <div class="bg-white p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Active Ticket Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Scrollable list of assigned jobs with machine, town, status and schedule.</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-100">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="app-table">
                            <thead>
                                <tr><th>Ticket</th><th>Machine</th><th>Town</th><th>Status</th><th>Scheduled</th></tr>
                            </thead>
                            <tbody>
                                @forelse($assignedTickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('service-tickets.show', $ticket) }}" class="font-black text-slate-950 hover:text-teal-700 hover:underline">{{ $ticket->ticket_number }}</a>
                                            <div class="text-xs text-slate-500">{{ $ticket->title }}</div>
                                        </td>
                                        <td>{{ $ticket->machine?->machine_name ?: 'Unknown' }}<div class="text-xs text-slate-500">{{ $ticket->machine?->serial_number }}</div></td>
                                        <td>{{ $ticket->site?->city ?: 'Unknown' }}</td>
                                        <td><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-bold text-blue-800">{{ str_replace('_', ' ', $ticket->status) }}</span></td>
                                        <td>{{ $ticket->scheduled_for?->format('d M H:i') ?? 'Not set' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">You have no active assigned tickets.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
