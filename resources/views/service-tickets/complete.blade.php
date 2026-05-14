<x-layouts.app :title="'Complete '.$ticket->ticket_number">
    @php($machine = $ticket->machine)
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">{{ $ticket->ticket_number }}</div>
            <h1 class="mt-1 text-2xl font-black">Complete Job Review</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $machine->machine_name }} / {{ $machine->serial_number }} / {{ $ticket->site->name }}</p>
        </div>
        <a href="{{ route('service-tickets.show', $ticket) }}" class="app-button-secondary">Back to ticket</a>
    </div>

    <form method="post" action="{{ route('service-tickets.complete.update', $ticket) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <section class="app-panel rounded-xl p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-black">Onsite Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Update anything that changed, then tick each row to confirm it has been checked.</p>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">{{ $machine->manufacturer }} {{ $machine->model }}</div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach([
                    'machine_name' => ['label' => 'Machine name', 'value' => $machine->machine_name, 'type' => 'text'],
                    'location' => ['label' => 'Location', 'value' => $machine->location, 'type' => 'text'],
                    'ip_address' => ['label' => 'IP address', 'value' => $machine->ip_address, 'type' => 'text'],
                    'hostname' => ['label' => 'Hostname', 'value' => $machine->hostname, 'type' => 'text'],
                    'mac_address' => ['label' => 'MAC address', 'value' => $machine->mac_address, 'type' => 'text'],
                    'subnet_mask' => ['label' => 'Subnet mask', 'value' => $machine->subnet_mask, 'type' => 'text'],
                    'gateway' => ['label' => 'Gateway', 'value' => $machine->gateway, 'type' => 'text'],
                    'primary_dns' => ['label' => 'Primary DNS', 'value' => $machine->primary_dns, 'type' => 'text'],
                    'secondary_dns' => ['label' => 'Secondary DNS', 'value' => $machine->secondary_dns, 'type' => 'text'],
                    'network_vlan' => ['label' => 'VLAN', 'value' => $machine->network_vlan, 'type' => 'text'],
                    'snmp_version' => ['label' => 'SNMP version', 'value' => $machine->snmp_version, 'type' => 'text'],
                    'snmp_community' => ['label' => 'SNMP community', 'value' => $machine->snmp_community, 'type' => 'text'],
                    'expected_report_sender_email' => ['label' => 'Expected report sender', 'value' => $machine->expected_report_sender_email, 'type' => 'email'],
                ] as $field => $meta)
                    @php($fieldHasError = $errors->has('machine.'.$field) || $errors->has('verified_fields.'.$field))
                    <div class="grid gap-2 rounded-lg border {{ $fieldHasError ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' }} p-3 sm:grid-cols-[1fr_auto] sm:items-end">
                        <label class="block text-sm font-semibold text-slate-700">{{ $meta['label'] }}
                            <input name="machine[{{ $field }}]" type="{{ $meta['type'] }}" value="{{ old('machine.'.$field, $meta['value']) }}" class="mt-1.5 w-full rounded-lg {{ $errors->has('machine.'.$field) ? 'border-rose-400 bg-white ring-2 ring-rose-100' : 'border-zinc-300' }} px-3 py-2">
                        </label>
                        <label class="flex items-center gap-2 rounded-md {{ $errors->has('verified_fields.'.$field) ? 'bg-rose-100 text-rose-800 ring-2 ring-rose-200' : 'bg-slate-50 text-slate-700' }} px-3 py-2 text-sm font-bold">
                            <input type="checkbox" name="verified_fields[{{ $field }}]" value="1" @checked(old('verified_fields.'.$field))>
                            Correct
                        </label>
                    </div>
                @endforeach

                @php($dhcpHasError = $errors->has('verified_fields.dhcp_enabled'))
                <div class="grid gap-2 rounded-lg border {{ $dhcpHasError ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' }} p-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    <div>
                        <div class="text-sm font-semibold text-slate-700">DHCP / static</div>
                        <label class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-900">
                            <input type="checkbox" name="machine[dhcp_enabled]" value="1" @checked(old('machine.dhcp_enabled', $machine->dhcp_enabled))>
                            DHCP enabled
                        </label>
                    </div>
                    <label class="flex items-center gap-2 rounded-md {{ $dhcpHasError ? 'bg-rose-100 text-rose-800 ring-2 ring-rose-200' : 'bg-slate-50 text-slate-700' }} px-3 py-2 text-sm font-bold">
                        <input type="checkbox" name="verified_fields[dhcp_enabled]" value="1" @checked(old('verified_fields.dhcp_enabled'))>
                        Correct
                    </label>
                </div>

                @php($notesHasError = $errors->has('machine.network_notes') || $errors->has('verified_fields.network_notes'))
                <div class="rounded-lg border {{ $notesHasError ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' }} p-3 lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Network notes
                        <textarea name="machine[network_notes]" class="mt-1.5 h-20 w-full rounded-lg {{ $errors->has('machine.network_notes') ? 'border-rose-400 bg-white ring-2 ring-rose-100' : 'border-zinc-300' }} px-3 py-2">{{ old('machine.network_notes', $machine->network_notes) }}</textarea>
                    </label>
                    <label class="mt-2 flex items-center gap-2 rounded-md {{ $errors->has('verified_fields.network_notes') ? 'bg-rose-100 text-rose-800 ring-2 ring-rose-200' : 'bg-slate-50 text-slate-700' }} px-3 py-2 text-sm font-bold">
                        <input type="checkbox" name="verified_fields[network_notes]" value="1" @checked(old('verified_fields.network_notes'))>
                        Correct
                    </label>
                </div>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Final Checks</h2>
                <div class="mt-4 space-y-2">
                    @foreach($functionalChecks as $field => $label)
                        <label class="flex items-center gap-3 rounded-lg border {{ $errors->has('functional_checks.'.$field) ? 'border-rose-300 bg-rose-50 text-rose-800 ring-2 ring-rose-100' : 'border-slate-200 bg-white text-slate-900' }} p-3 text-sm font-black">
                            <input type="checkbox" name="functional_checks[{{ $field }}]" value="1" @checked(old('functional_checks.'.$field))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Resolution Notes</h2>
                <label class="mt-4 block text-sm font-semibold text-slate-700">What was done?
                    <textarea name="resolution" class="mt-2 h-44 w-full rounded-lg {{ $errors->has('resolution') ? 'border-rose-400 bg-rose-50 ring-2 ring-rose-100' : 'border-zinc-300' }} px-3 py-2.5" required>{{ old('resolution', $ticket->resolution) }}</textarea>
                </label>
            </section>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                <div class="font-black">Please complete the highlighted items before resolving this ticket.</div>
                <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-lg backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-600">Submitting this review will mark the ticket as resolved.</p>
                <button class="app-button">Confirm checks and resolve ticket</button>
            </div>
        </div>
    </form>
</x-layouts.app>
