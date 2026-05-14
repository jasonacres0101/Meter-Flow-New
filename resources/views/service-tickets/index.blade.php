<x-layouts.app title="Service Tickets">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Repairs and maintenance</div>
            <h1 class="mt-1 text-2xl font-black">Service Tickets</h1>
            <p class="mt-1 text-sm text-slate-500">Track machine repair, maintenance and engineer responses.</p>
        </div>
        @unless(auth()->user()->isEngineer())
            <a href="{{ route('service-tickets.create') }}" class="app-button">Create ticket</a>
        @endunless
    </div>

    @if(auth()->user()->isEngineer() && $areas->isNotEmpty())
        <form method="get" class="app-panel mb-5 rounded-lg p-4">
            <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <label class="app-field">Filter available work by town
                    <select name="area" class="app-field-control">
                        <option value="">All towns</option>
                        @foreach($areas as $area)
                            <option value="{{ $area }}" @selected($selectedArea === $area)>{{ $area }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex gap-2">
                    <button class="app-button">Apply</button>
                    <a href="{{ route('service-tickets.index') }}" class="app-button-secondary">Clear</a>
                </div>
            </div>
        </form>
    @endif

    @if(auth()->user()->isEngineer())
        <section class="space-y-3">
            @forelse($tickets as $ticket)
                @php($isOfferedOnly = blank($ticket->assigned_engineer_id))
                @php($priorityTone = match($ticket->priority) {
                    'urgent' => 'bg-rose-100 text-rose-800 ring-rose-200',
                    'high' => 'bg-amber-100 text-amber-900 ring-amber-200',
                    'low' => 'bg-slate-100 text-slate-700 ring-slate-200',
                    default => 'bg-blue-100 text-blue-800 ring-blue-200',
                })
                @php($statusTone = match($ticket->status) {
                    'resolved', 'closed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                    'in_progress' => 'bg-teal-100 text-teal-800 ring-teal-200',
                    'scheduled' => 'bg-blue-100 text-blue-800 ring-blue-200',
                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                })
                <article class="app-panel rounded-xl p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a class="font-black text-slate-950 hover:underline" href="{{ route('service-tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                                <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $statusTone }}">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $priorityTone }}">{{ ucfirst($ticket->priority) }}</span>
                                @if($isOfferedOnly)
                                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">Waiting acceptance</span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-lg font-black text-slate-950">{{ $ticket->title }}</h2>
                            <div class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                                <div>
                                    @if($isOfferedOnly)
                                        <span class="font-bold text-slate-500">Town: {{ $ticket->site?->city ?: 'Unknown' }}</span>
                                    @else
                                        <span class="font-bold text-slate-500">Area:</span> {{ $ticket->site?->city ?: $ticket->site?->name }}
                                    @endif
                                </div>
                                <div><span class="font-bold text-slate-500">Machine:</span> {{ $ticket->machine?->manufacturer ?: 'Unknown' }} {{ $ticket->machine?->model }}</div>
                                <div><span class="font-bold text-slate-500">Updated:</span> {{ $ticket->updated_at->format('d M H:i') }}</div>
                            </div>
                            @if($isOfferedOnly)
                                <p class="mt-2 text-sm text-slate-500">Accept ticket to view full machine and site details.</p>
                            @else
                                <p class="mt-2 text-sm text-slate-500">{{ $ticket->client->name }} / {{ $ticket->site->name }} / {{ $ticket->machine->machine_name }}</p>
                            @endif
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
                                        <span class="rounded-full bg-slate-100 px-2 py-1 font-bold text-slate-700">{{ $label }}: {{ ucfirst($ticket->{$field}) }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col">
                            <a href="{{ route('service-tickets.show', $ticket) }}" class="app-button-secondary">View ticket</a>
                            @if($isOfferedOnly)
                                <form method="post" action="{{ route('service-tickets.accept', $ticket) }}">
                                    @csrf
                                    <button class="app-button w-full">Accept job</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <section class="app-panel rounded-xl p-8 text-center text-slate-500">No service tickets yet.</section>
            @endforelse
        </section>
    @else
        <section class="app-panel overflow-hidden rounded-xl">
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th>Ticket</th><th>Machine</th><th>Engineer</th><th>Status</th><th>Priority</th><th>Updated</th></tr>
                    </thead>
                    <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td><a class="font-black text-slate-950 hover:underline" href="{{ route('service-tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a><div class="text-xs text-slate-500">{{ $ticket->title }}</div></td>
                            <td>{{ $ticket->machine->machine_name }}<div class="text-xs text-slate-500">{{ $ticket->client->name }} / {{ $ticket->site->name }} / {{ $ticket->site?->city }}</div></td>
                            <td>
                                @if($ticket->assignedEngineer)
                                    {{ $ticket->assignedEngineer->name }}
                                @elseif($ticket->engineerOffers->isNotEmpty())
                                    Offered to {{ $ticket->engineerOffers->whereNull('withdrawn_at')->count() }} engineer{{ $ticket->engineerOffers->whereNull('withdrawn_at')->count() === 1 ? '' : 's' }}
                                @else
                                    Unassigned
                                @endif
                            </td>
                            <td><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-bold text-blue-800">{{ str_replace('_', ' ', $ticket->status) }}</span></td>
                            <td>{{ ucfirst($ticket->priority) }}</td>
                            <td>{{ $ticket->updated_at->format('d M H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-slate-500">No service tickets yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layouts.app>
