<x-layouts.app title="Create Service Ticket">
    <div class="mb-6">
        <div class="text-sm font-bold uppercase tracking-wide text-teal-700">New request</div>
        <h1 class="mt-1 text-2xl font-black">Create Service Ticket</h1>
        <p class="mt-1 text-sm text-slate-500">Raise a repair or maintenance job against a specific machine.</p>
    </div>

    <form method="post" action="{{ route('service-tickets.store') }}" class="app-panel rounded-xl p-5">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold text-slate-700">Machine
                <select name="machine_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}" @selected(old('machine_id', request('machine_id')) == $machine->id)>{{ $machine->client->name }} / {{ $machine->site->name }} / {{ $machine->machine_name }} / {{ $machine->serial_number }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Title
                <input name="title" value="{{ old('title') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Requested date
                <input name="requested_for" type="datetime-local" value="{{ old('requested_for') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Issue type
                <select name="issue_type" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    @foreach(['repair' => 'Repair', 'maintenance' => 'Maintenance', 'install' => 'Install', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('issue_type', 'repair') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Priority
                <select name="priority" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">
                    @foreach(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <label class="mt-4 block text-sm font-semibold text-slate-700">Fault or maintenance details
            <textarea name="description" class="mt-2 h-36 w-full rounded-lg border-zinc-300 px-3 py-2.5">{{ old('description') }}</textarea>
        </label>
        <div class="mt-5 rounded-lg border border-slate-200 bg-white p-4">
            <div class="font-black text-slate-950">Skill requirements</div>
            <p class="mt-1 text-sm text-slate-500">Set the skills needed for this visit. Tickets are only offered to engineers who support the machine manufacturer and meet these levels.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    'required_networking_level' => 'Networking',
                    'required_vlan_level' => 'VLANs',
                    'required_dhcp_static_ip_level' => 'DHCP / static IPs',
                    'required_dns_level' => 'DNS',
                    'required_routing_level' => 'Routing',
                    'required_firewall_level' => 'Firewall',
                ] as $field => $label)
                    <label class="app-field">{{ $label }}
                        <select name="{{ $field }}" class="app-field-control">
                            @foreach($skillLevels as $value => $text)
                                <option value="{{ $value }}" @selected(old($field, 'none') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="font-black text-slate-950">Offer ticket to engineers</div>
            <p class="mt-1 text-sm text-slate-500">Select specific engineers, or leave everyone unticked to offer the ticket to all matching engineers. Unsupported manufacturers and insufficient skill levels are filtered out automatically.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse($engineers as $engineer)
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 text-sm">
                        <input name="engineer_ids[]" type="checkbox" value="{{ $engineer->id }}" @checked(collect(old('engineer_ids', []))->contains($engineer->id))>
                        <span>
                            <span class="block font-bold text-slate-950">{{ $engineer->name }}</span>
                            <span class="block text-xs text-slate-500">{{ $engineer->email }}</span>
                            <span class="mt-1 block text-xs text-slate-500">
                                {{ $engineer->supportedManufacturers->map(fn($manufacturer) => $manufacturer->name.' / '.ucfirst($manufacturer->pivot->skill_level))->join(', ') ?: 'No manufacturers set' }}
                            </span>
                        </span>
                    </label>
                @empty
                    <p class="rounded-lg bg-white p-3 text-sm text-slate-500">No engineers are linked to this company yet.</p>
                @endforelse
            </div>
        </div>
        @if($errors->any())<div class="mt-4 rounded-lg bg-rose-50 p-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>@endif
        <button class="app-button mt-5">Create ticket</button>
    </form>
</x-layouts.app>
