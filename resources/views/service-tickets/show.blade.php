<x-layouts.app :title="$ticket->ticket_number">
    @php($isOfferedOnly = auth()->user()->isEngineer() && blank($ticket->assigned_engineer_id))
    @php($ticketStatusOptions = auth()->user()->isEngineer()
        ? ['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In progress']
        : ['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'])
    @php($activeJobTimer = $ticket->timeLogs->first(fn($log) => $log->user_id === auth()->id() && blank($log->stopped_at)))
    @php($loggedSeconds = (int) $ticket->timeLogs->sum(fn($log) => $log->duration_seconds ?? 0))
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
    <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-950 px-5 py-4 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">{{ $ticket->ticket_number }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $statusTone }}">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $priorityTone }}">{{ ucfirst($ticket->priority) }}</span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">{{ $ticket->title }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        @if($isOfferedOnly)
                            Town: {{ $ticket->site?->city ?: 'Unknown' }} / {{ $ticket->machine?->manufacturer ?: 'Unknown' }} {{ $ticket->machine?->model }} / Accept ticket to view full site and machine details.
                        @else
                            {{ $ticket->client->name }} / {{ $ticket->site->name }} / {{ $ticket->machine->machine_name }} / {{ $ticket->machine->serial_number }}
                        @endif
                    </p>
                </div>
                @unless($isOfferedOnly)
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        @if($ticket->machine->ip_address)
                            <a
                                href="http://{{ $ticket->machine->ip_address }}"
                                target="_blank"
                                rel="noopener"
                                onclick="return confirm('Make sure you are on the same network as this printer/copier before continuing.');"
                                class="inline-flex items-center justify-center rounded-md bg-teal-300 px-3 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200"
                            >
                                Open device web panel
                            </a>
                        @endif
                        <a href="{{ route('machines.show', $ticket->machine) }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">View machine</a>
                    </div>
                @endunless
            </div>
        </div>

        @unless($isOfferedOnly)
            <div class="grid gap-px bg-slate-200 md:grid-cols-4">
                <div class="bg-white p-4"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Engineer</div><div class="mt-1 font-black text-slate-950">{{ $ticket->assignedEngineer?->name ?? 'Unassigned' }}</div></div>
                <div class="bg-white p-4"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Scheduled</div><div class="mt-1 font-black text-slate-950">{{ $ticket->scheduled_for?->format('d M H:i') ?? 'Not set' }}</div></div>
                <div class="bg-white p-4"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</div><div class="mt-1 font-black text-slate-950">{{ $ticket->site?->city ?: $ticket->site?->name }}</div></div>
                <div class="bg-white p-4"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Machine</div><div class="mt-1 font-black text-slate-950">{{ $ticket->machine?->manufacturer }} {{ $ticket->machine?->model }}</div></div>
            </div>
        @endunless
    </section>

    @if($isOfferedOnly)
        <div class="mb-5 grid gap-4 md:grid-cols-4">
            <x-stat label="Status" :value="str_replace('_', ' ', $ticket->status)" tone="blue" />
            <x-stat label="Priority" :value="ucfirst($ticket->priority)" tone="amber" />
            <x-stat label="Engineer" :value="$ticket->assignedEngineer?->name ?? 'Unassigned'" tone="teal" />
            <x-stat label="Scheduled" :value="$ticket->scheduled_for?->format('d M H:i') ?? 'Not set'" tone="slate" />
        </div>
    @endif

    @unless($isOfferedOnly)
        <section class="app-panel mt-6 overflow-hidden rounded-xl p-0">
            <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="bg-slate-950 p-5 text-white">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-black uppercase tracking-wide text-teal-200">Engineer workspace</div>
                            <h2 class="mt-1 text-lg font-black">Job Timer</h2>
                            <p class="mt-1 text-sm text-slate-300">Manual time recording for this ticket. You control when it starts and stops.</p>
                        </div>
                        <span class="rounded-full {{ $activeJobTimer ? 'bg-emerald-400 text-emerald-950' : 'bg-slate-800 text-slate-300' }} px-3 py-1 text-xs font-black uppercase tracking-wide">
                            {{ $activeJobTimer ? 'Running' : 'Stopped' }}
                        </span>
                    </div>

                    <div
                        class="mt-6 font-mono text-5xl font-black tabular-nums tracking-normal"
                        data-job-timer-display
                        data-started-at="{{ $activeJobTimer?->started_at?->toIso8601String() }}"
                    >
                        00:00:00
                    </div>

                    <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-white/10 p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Started</div>
                            <div class="mt-1 font-black">{{ $activeJobTimer?->started_at?->format('d M Y H:i') ?? 'Not running' }}</div>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Logged so far</div>
                            <div class="mt-1 font-black">
                                {{ intdiv($loggedSeconds, 3600) }}h {{ intdiv($loggedSeconds % 3600, 60) }}m
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->isEngineer() && $canUpdateTicket)
                        @if($activeJobTimer)
                            <form method="post" action="{{ route('service-tickets.timer.stop', $ticket) }}" class="mt-5 space-y-3">
                                @csrf
                                <label class="block text-sm font-semibold text-slate-200">Stop note <span class="text-rose-300">*</span>
                                    <textarea name="notes" required class="mt-2 h-20 w-full rounded-lg border-slate-700 bg-slate-900 px-3 py-2.5 text-white placeholder:text-slate-500" placeholder="What did you do during this work session?"></textarea>
                                </label>
                                <button class="w-full rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-rose-600">Stop job timer</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('service-tickets.timer.start', $ticket) }}" class="mt-5">
                                @csrf
                                <button class="w-full rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-black text-emerald-950 shadow-sm transition hover:bg-emerald-300">Start job</button>
                            </form>
                        @endif
                    @endif

                    @if(auth()->user()->isEngineer() && $canUpdateTicket && $ticket->status !== \App\Models\ServiceTicket::STATUS_RESOLVED)
                        <a href="{{ route('service-tickets.complete.edit', $ticket) }}" class="mt-3 flex w-full items-center justify-center rounded-lg bg-teal-300 px-4 py-3 text-sm font-black text-slate-950 shadow-sm transition hover:bg-teal-200">
                            Complete job and review machine
                        </a>
                    @endif

                    @if($errors->has('timer'))
                        <div class="mt-4 rounded-lg bg-rose-100 p-3 text-sm font-bold text-rose-900">{{ $errors->first('timer') }}</div>
                    @endif
                </div>

                <div class="p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Time log</h3>
                        <div class="text-sm font-bold text-slate-500">{{ $ticket->timeLogs->count() }} session{{ $ticket->timeLogs->count() === 1 ? '' : 's' }}</div>
                    </div>
                    <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-2">
                        @forelse($ticket->timeLogs->sortByDesc('started_at') as $timeLog)
                            @php($durationSeconds = $timeLog->duration_seconds ?? ($timeLog->stopped_at ? $timeLog->started_at->diffInSeconds($timeLog->stopped_at) : 0))
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="font-black text-slate-950">{{ $timeLog->engineer?->name ?? 'Engineer' }}</div>
                                    <div class="text-xs font-bold uppercase tracking-wide {{ $timeLog->stopped_at ? 'text-slate-500' : 'text-emerald-700' }}">{{ $timeLog->stopped_at ? 'Logged' : 'Running now' }}</div>
                                </div>
                                <div class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                                    <div><span class="font-bold text-slate-500">Start:</span> {{ $timeLog->started_at->format('d M H:i') }}</div>
                                    <div><span class="font-bold text-slate-500">Stop:</span> {{ $timeLog->stopped_at?->format('d M H:i') ?? 'Active' }}</div>
                                    <div><span class="font-bold text-slate-500">Time:</span> {{ intdiv((int) $durationSeconds, 3600) }}h {{ intdiv(((int) $durationSeconds) % 3600, 60) }}m</div>
                                </div>
                                @if($timeLog->notes)
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $timeLog->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No job time has been logged yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        @if($activeJobTimer)
            <script>
                (() => {
                    const display = document.querySelector('[data-job-timer-display]');
                    if (! display || ! display.dataset.startedAt) return;

                    const startedAt = Date.parse(display.dataset.startedAt);
                    const pad = (value) => String(value).padStart(2, '0');
                    const render = () => {
                        const elapsed = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
                        const hours = Math.floor(elapsed / 3600);
                        const minutes = Math.floor((elapsed % 3600) / 60);
                        const seconds = elapsed % 60;
                        display.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                    };

                    render();
                    setInterval(render, 1000);
                })();
            </script>
        @endif
    @endunless

    @if($canAccept)
        <section class="app-panel mt-5 rounded-xl border-teal-200 bg-teal-50 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-teal-950">This ticket is available to accept</h2>
                    <p class="mt-1 text-sm text-teal-800">Accepting assigns the ticket to you and removes it from the other offered engineers.</p>
                </div>
                <form method="post" action="{{ route('service-tickets.accept', $ticket) }}">
                    @csrf
                    <button class="app-button">Accept ticket</button>
                </form>
            </div>
        </section>
    @endif

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-700">Job overview</div>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Ticket Details and Update</h2>
                </div>
                <div class="text-sm font-bold text-slate-500">Opened {{ $ticket->created_at->format('d M Y H:i') }}</div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.85fr_1.15fr]">
            <div class="bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Issue Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ ucfirst($ticket->issue_type) }} request</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $priorityTone }}">{{ ucfirst($ticket->priority) }}</span>
                </div>

                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $ticket->description }}</p>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-black text-slate-950">Required skills</div>
                        @if($isOfferedOnly)
                            <span class="text-xs font-bold text-slate-500">Shown before acceptance</span>
                        @endif
                    </div>
                    @if($isOfferedOnly)
                        <p class="mt-1 text-xs text-slate-500">These are shown before acceptance so you can decide if the work matches your skills.</p>
                    @endif
                    <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                        @foreach([
                            'required_networking_level' => 'Networking',
                            'required_vlan_level' => 'VLANs',
                            'required_dhcp_static_ip_level' => 'DHCP / static IPs',
                            'required_dns_level' => 'DNS',
                            'required_routing_level' => 'Routing',
                            'required_firewall_level' => 'Firewall',
                        ] as $field => $label)
                            @php($skillLevel = $ticket->{$field} ?? 'none')
                            <div class="flex items-center justify-between rounded-md border {{ $skillLevel === 'none' ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50' }} px-3 py-2">
                                <span class="font-bold text-slate-500">{{ $label }}</span>
                                <span class="font-black {{ $skillLevel === 'none' ? 'text-slate-700' : 'text-teal-800' }}">{{ ucfirst($skillLevel) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($ticket->resolution)
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                        <div class="font-black">Resolution</div>
                        <p class="mt-2 whitespace-pre-line">{{ $ticket->resolution }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white p-5">
                @if($canUpdateTicket)
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">Update Ticket</h3>
                            <p class="mt-1 text-sm text-slate-500">Set progress, add notes, and attach repair photos.</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 {{ $statusTone }}">{{ str_replace('_', ' ', $ticket->status) }}</span>
                    </div>

                    <form method="post" action="{{ route('service-tickets.update', $ticket) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')
                    @unless(auth()->user()->isEngineer())
                        <label class="block text-sm font-semibold text-slate-700">Engineer
                            <select name="assigned_engineer_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                                <option value="">Unassigned</option>
                                @foreach($engineers as $engineer)
                                    <option value="{{ $engineer->id }}" @selected(old('assigned_engineer_id', $ticket->assigned_engineer_id) == $engineer->id)>{{ $engineer->name }} / {{ $engineer->email }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endunless
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-semibold text-slate-700">Status
                                <select name="status" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                                    @foreach($ticketStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm font-semibold text-slate-700">Engineer date
                                <input name="scheduled_for" type="datetime-local" value="{{ old('scheduled_for', $ticket->scheduled_for?->format('Y-m-d\\TH:i')) }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                            </label>
                        </div>

                        <label class="block text-sm font-semibold text-slate-700">Work notes
                            <textarea name="notes" class="mt-2 h-24 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="Add an update for the customer or office team.">{{ old('notes') }}</textarea>
                        </label>

                        <label class="block text-sm font-semibold text-slate-700">Resolution draft
                            <textarea name="resolution" class="mt-2 h-24 w-full rounded-lg border-zinc-300 px-3 py-2.5" placeholder="Use the completion review to formally resolve the ticket.">{{ old('resolution', $ticket->resolution) }}</textarea>
                        </label>

                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                            <label class="block text-sm font-semibold text-slate-700">Photos
                                <input name="photos[]" type="file" multiple accept="image/*" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5">
                            </label>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Attach repair, damage, or completion photos.</p>
                        </div>

                        @if($errors->any())<div class="rounded-lg bg-rose-50 p-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>@endif
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <button class="app-button">Save update</button>
                            @if(auth()->user()->isEngineer() && $canUpdateTicket && $ticket->status !== \App\Models\ServiceTicket::STATUS_RESOLVED)
                                <a href="{{ route('service-tickets.complete.edit', $ticket) }}" class="app-button-secondary">Complete job review</a>
                            @endif
                        </div>
                    </form>
                @else
                    <h3 class="text-lg font-black text-slate-950">Waiting for acceptance</h3>
                    <p class="mt-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Accept this ticket before adding dates, resolutions or photos.</p>
                @endif
            </div>
        </div>
    </section>

    @unless($isOfferedOnly)
        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-200">Connection details</div>
                        <h2 class="mt-1 text-xl font-black text-white">Network Settings</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Use these details while onsite or connected to the customer network.</p>
                    </div>
                    @if($ticket->machine->ip_address)
                        <a
                            href="http://{{ $ticket->machine->ip_address }}"
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
                        <div class="mt-2 break-words font-mono text-3xl font-black tracking-normal text-slate-950">{{ $ticket->machine->ip_address ?: 'Not set' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ $ticket->machine->dhcp_enabled ? 'DHCP enabled' : 'Static IP' }}</span>
                            @if($ticket->machine->network_vlan)
                                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-800 ring-1 ring-teal-200">VLAN {{ $ticket->machine->network_vlan }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Hostname</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $ticket->machine->hostname ?: 'Not set' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Gateway</div>
                            <div class="mt-1 break-words font-black text-slate-950">{{ $ticket->machine->gateway ?: 'Not set' }}</div>
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
                            'MAC address' => $ticket->machine->mac_address,
                            'Subnet mask' => $ticket->machine->subnet_mask,
                            'Gateway' => $ticket->machine->gateway,
                            'Primary DNS' => $ticket->machine->primary_dns,
                            'Secondary DNS' => $ticket->machine->secondary_dns,
                            'VLAN' => $ticket->machine->network_vlan,
                            'SNMP version' => $ticket->machine->snmp_version,
                            'SNMP community' => $ticket->machine->snmp_community,
                        ] as $label => $value)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 shadow-sm shadow-slate-100">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                                <div class="mt-1 break-words font-black text-slate-950">{{ filled($value) ? $value : 'Not set' }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($ticket->machine->network_notes)
                        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-950">
                            <div class="font-black">Network notes</div>
                            <p class="mt-1 whitespace-pre-line">{{ $ticket->machine->network_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endunless

    @unless(auth()->user()->isEngineer())
        <section class="app-panel mt-6 rounded-xl p-5">
            <h2 class="text-lg font-black">Offered Engineers</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @forelse($ticket->engineerOffers as $offer)
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="font-bold text-slate-950">{{ $offer->engineer->name }}</div>
                        <div class="text-xs text-slate-500">{{ $offer->engineer->email }}</div>
                        <div class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                            @if($offer->accepted_at)
                                Accepted {{ $offer->accepted_at->format('d M H:i') }}
                            @elseif($offer->withdrawn_at)
                                Removed after acceptance
                            @else
                                Awaiting response
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No engineers were offered this ticket.</p>
                @endforelse
            </div>
        </section>
    @endunless

    @if(auth()->user()->isEngineer() && $canUpdateTicket)
        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gradient-to-r from-zinc-950 via-slate-900 to-slate-800 px-5 py-5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-amber-200">Secure engineer access</div>
                        <h2 class="mt-1 text-xl font-black text-white">Machine Password Access</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Reveal credentials only when needed for this assigned ticket.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full {{ $credentialsUnlocked ? 'bg-emerald-300/20 text-emerald-100 ring-emerald-300/40' : 'bg-white/10 text-slate-200 ring-white/20' }} px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1">
                            {{ $credentialsUnlocked ? 'Unlocked' : 'Locked' }}
                        </span>
                        @if($credentialsUnlocked)
                            <form method="post" action="{{ route('service-tickets.credential-access.destroy', $ticket) }}">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-black text-slate-950 shadow-sm transition hover:bg-amber-50">Lock passwords</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if(! auth()->user()->hasEngineerPin())
                <form method="post" action="{{ route('engineer-pin.update') }}" class="grid gap-4 p-5 lg:grid-cols-[0.8fr_1.2fr]">
                    @csrf
                    @method('PUT')
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="text-sm font-black text-amber-950">PIN setup required</div>
                        <p class="mt-1 text-sm leading-6 text-amber-900">Create a private engineer PIN before revealing machine credentials on ticket screens.</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                        <label class="block text-sm font-semibold text-slate-700">New PIN
                            <input name="pin" type="password" inputmode="numeric" placeholder="Create 4-8 digit PIN" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Confirm PIN
                            <input name="pin_confirmation" type="password" inputmode="numeric" placeholder="Confirm PIN" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                        </label>
                        <button class="app-button">Save PIN</button>
                    </div>
                </form>
            @elseif(! $credentialsUnlocked)
                <div class="grid gap-px bg-slate-200 lg:grid-cols-[0.72fr_1.28fr]">
                    <div class="bg-slate-950 p-5 text-white">
                        <div class="text-xs font-black uppercase tracking-wide text-amber-200">PIN required</div>
                        <div class="mt-2 text-2xl font-black">Credentials are locked</div>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Enter your engineer PIN to reveal saved usernames, passwords and admin links for this machine.</p>
                        <div class="mt-4 rounded-lg border border-white/10 bg-white/10 p-3 text-xs font-semibold leading-5 text-slate-300">
                            Access is limited to the assigned engineer and this active ticket.
                        </div>
                    </div>
                    <form method="post" action="{{ route('service-tickets.credential-access.store', $ticket) }}" class="grid gap-3 bg-white p-5 md:grid-cols-[1fr_auto] md:items-end">
                    @csrf
                        <label class="block text-sm font-semibold text-slate-700">Engineer PIN
                            <input name="pin" type="password" inputmode="numeric" placeholder="Enter engineer PIN" class="mt-2 rounded-lg border-zinc-300 px-3 py-2.5">
                        </label>
                        <button class="app-button">Reveal passwords</button>
                    </form>
                </div>
            @else
                <div class="grid gap-3 p-5">
                    @forelse($ticket->machine->credentials as $credential)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm shadow-slate-100">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Credential</div>
                                    <div class="mt-1 font-black text-slate-950">{{ $credential->label }}</div>
                                    <div class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                        <div><span class="font-bold text-slate-800">Username:</span> {{ $credential->username ?: 'No username' }}</div>
                                        <div><span class="font-bold text-slate-800">URL:</span> {{ $credential->url ?: 'No URL saved' }}</div>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-sm text-white shadow-sm">{{ $credential->password ?: 'No password stored' }}</div>
                            </div>
                            @if($credential->notes)<p class="mt-3 text-sm text-slate-600">{{ $credential->notes }}</p>@endif
                        </div>
                    @empty
                        <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No credentials are saved for this machine.</p>
                    @endforelse
                </div>
            @endif
        </section>
    @endif

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-700">Service record</div>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Machine Service History</h2>
                    <p class="mt-1 text-sm text-slate-500">All service tickets recorded against this machine.</p>
                </div>
                @unless($isOfferedOnly)
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-right">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Serial number</div>
                        <div class="mt-1 font-mono text-sm font-black text-slate-950">{{ $ticket->machine->serial_number }}</div>
                    </div>
                @endunless
            </div>
        </div>

        @if($machineTicketTimeline->isNotEmpty())
            <div class="max-h-[38rem] space-y-4 overflow-y-auto bg-slate-50 p-5 pr-2">
                @foreach($machineTicketTimeline as $historyTicket)
                    <article class="overflow-hidden rounded-xl border {{ $historyTicket->is($ticket) ? 'border-teal-300 bg-white shadow-sm shadow-teal-100' : 'border-slate-200 bg-white shadow-sm shadow-slate-100' }}">
                        <div class="{{ $historyTicket->is($ticket) ? 'bg-teal-50' : 'bg-white' }} border-b border-slate-200 p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('service-tickets.show', $historyTicket) }}" class="font-black text-slate-950 hover:underline">{{ $historyTicket->ticket_number }}</a>
                                        @if($historyTicket->is($ticket))
                                            <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-black uppercase tracking-wide text-teal-800">Current</span>
                                        @endif
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-slate-600">{{ str_replace('_', ' ', $historyTicket->status) }}</span>
                                        <span class="rounded-full {{ $historyTicket->priority === 'urgent' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }} px-2 py-0.5 text-xs font-bold uppercase tracking-wide">{{ ucfirst($historyTicket->priority) }}</span>
                                    </div>
                                    <h3 class="mt-1 font-bold text-slate-900">{{ $historyTicket->title }}</h3>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Opened {{ $historyTicket->created_at->format('d M Y H:i') }}
                                        @if($historyTicket->resolved_at)
                                            / Resolved {{ $historyTicket->resolved_at->format('d M Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="grid gap-2 text-sm text-slate-600 sm:min-w-64">
                                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><span class="font-bold text-slate-500">Engineer:</span> {{ $historyTicket->assignedEngineer?->name ?? 'Unassigned' }}</div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><span class="font-bold text-slate-500">Opened by:</span> {{ $historyTicket->openedBy?->name ?? 'Unknown' }}</div>
                                </div>
                            </div>
                        </div>

                        @if($historyTicket->updates->isNotEmpty())
                            <div class="border-l-2 border-slate-200 px-4 py-4 ml-4">
                                @foreach($historyTicket->updates->sortByDesc('created_at') as $historyUpdate)
                                    <div class="relative pb-4 last:pb-0">
                                        <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-teal-500 ring-4 ring-white"></span>
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="text-sm font-bold text-slate-900">
                                                {{ $historyUpdate->user?->name ?? 'Unknown user' }}
                                                @if($historyUpdate->status)
                                                    <span class="font-semibold text-slate-500">{{ str_replace('_', ' ', $historyUpdate->status) }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500">{{ $historyUpdate->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                        @if($historyUpdate->scheduled_for)<div class="mt-1 text-sm font-semibold text-blue-800">Scheduled for {{ $historyUpdate->scheduled_for->format('d M Y H:i') }}</div>@endif
                                        @if($historyUpdate->notes)<p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $historyUpdate->notes }}</p>@endif
                                        @if($historyUpdate->resolution)<p class="mt-2 whitespace-pre-line rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ $historyUpdate->resolution }}</p>@endif
                                        @if($historyUpdate->photos->isNotEmpty())
                                            <div class="mt-2 text-xs font-bold text-slate-500">{{ $historyUpdate->photos->count() }} photo{{ $historyUpdate->photos->count() === 1 ? '' : 's' }} attached</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="m-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500">No updates have been added to this ticket yet.</p>
                        @endif
                        @if($historyTicket->timeLogs->whereNotNull('notes')->isNotEmpty())
                            <div class="mx-4 mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <div class="text-xs font-black uppercase tracking-wide text-amber-800">Time log notes</div>
                                <div class="mt-3 space-y-3">
                                    @foreach($historyTicket->timeLogs->whereNotNull('notes')->sortByDesc('started_at') as $timeLog)
                                        <div class="rounded-md bg-white p-3 text-sm">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="font-black text-slate-950">{{ $timeLog->engineer?->name ?? 'Engineer' }}</div>
                                                <div class="text-xs font-bold text-slate-500">{{ $timeLog->started_at->format('d M H:i') }} / {{ intdiv((int) $timeLog->duration_seconds, 3600) }}h {{ intdiv(((int) $timeLog->duration_seconds) % 3600, 60) }}m</div>
                                            </div>
                                            <p class="mt-2 whitespace-pre-line text-slate-700">{{ $timeLog->notes }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p class="m-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Accept this ticket to view this machine's full service history.</p>
        @endif
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-5 py-4 text-white">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Current job activity</div>
                    <h2 class="mt-1 text-xl font-black">This Ticket Timeline</h2>
                </div>
                <div class="text-xs font-bold uppercase tracking-wide text-slate-300">{{ $ticket->ticket_number }}</div>
            </div>
        </div>
        <div class="max-h-96 space-y-4 overflow-y-auto bg-slate-50 p-5 pr-2">
            @foreach($ticket->updates->sortByDesc('created_at') as $update)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div class="font-bold text-slate-950">
                            {{ $update->user->name }}
                            @if($update->status)
                                <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-black uppercase tracking-wide text-slate-600">{{ str_replace('_', ' ', $update->status) }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">{{ $update->created_at->format('d M Y H:i') }}</div>
                    </div>
                    @if($update->scheduled_for)<div class="mt-2 text-sm font-semibold text-blue-800">Scheduled for {{ $update->scheduled_for->format('d M Y H:i') }}</div>@endif
                    @if($update->notes)<p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $update->notes }}</p>@endif
                    @if($update->resolution)<p class="mt-2 whitespace-pre-line rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ $update->resolution }}</p>@endif
                    @if($update->photos->isNotEmpty())
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach($update->photos as $photo)
                                <a href="{{ $photo->url() }}" target="_blank" class="block overflow-hidden rounded-lg border border-slate-200"><img src="{{ $photo->url() }}" alt="{{ $photo->original_name }}" class="h-32 w-full object-cover"></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
            @foreach($ticket->timeLogs->whereNotNull('notes')->sortByDesc('started_at') as $timeLog)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div class="font-bold text-slate-950">{{ $timeLog->engineer?->name ?? 'Engineer' }} <span class="text-sm font-semibold text-amber-800">time log note</span></div>
                        <div class="text-xs text-slate-500">{{ $timeLog->started_at->format('d M Y H:i') }}</div>
                    </div>
                    <div class="mt-2 text-sm font-semibold text-amber-900">Logged time: {{ intdiv((int) $timeLog->duration_seconds, 3600) }}h {{ intdiv(((int) $timeLog->duration_seconds) % 3600, 60) }}m</div>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $timeLog->notes }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
