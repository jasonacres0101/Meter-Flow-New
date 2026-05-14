@csrf
<div class="space-y-6">
    <section class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black">Machine Identity</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-semibold text-slate-700">Client<select name="client_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id', $machine->client_id ?? '') == $client->id)>{{ $client->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-semibold text-slate-700">Site<select name="site_id" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5">@foreach($sites as $site)<option value="{{ $site->id }}" @selected(old('site_id', $machine->site_id ?? '') == $site->id)>{{ $site->client->name }} - {{ $site->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-semibold text-slate-700">Manufacturer<select name="manufacturer_id" data-machine-manufacturer class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"><option value="">Choose manufacturer</option>@foreach($manufacturers as $manufacturer)<option value="{{ $manufacturer->id }}" @selected(old('manufacturer_id', $machine->machineModel?->manufacturer_id ?? '') == $manufacturer->id)>{{ $manufacturer->name }}</option>@endforeach</select></label>
            <label class="block text-sm font-semibold text-slate-700">Machine model<select name="machine_model_id" data-machine-model class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"><option value="">Choose machine model</option>@foreach($machineModels as $model)<option value="{{ $model->id }}" data-manufacturer-id="{{ $model->manufacturer_id }}" @selected(old('machine_model_id', $machine->machine_model_id ?? '') == $model->id)>{{ $model->model_name }}{{ is_null($model->company_id) ? ' (prebuilt)' : '' }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Select a manufacturer first. The saved manufacturer is taken from the selected machine model.</span></label>
            <label class="block text-sm font-semibold text-slate-700">Serial number<input name="serial_number" value="{{ old('serial_number', $machine->serial_number ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Machine name<input name="machine_name" value="{{ old('machine_name', $machine->machine_name ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Location<input name="location" value="{{ old('location', $machine->location ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Expected sender<input name="expected_report_sender_email" value="{{ old('expected_report_sender_email', $machine->expected_report_sender_email ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $machine->is_active ?? true))> Active machine</label>
        </div>
    </section>

    <section class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black">Network Settings</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <label class="block text-sm font-semibold text-slate-700">IP address<input name="ip_address" value="{{ old('ip_address', $machine->ip_address ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Hostname<input name="hostname" value="{{ old('hostname', $machine->hostname ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">MAC address<input name="mac_address" value="{{ old('mac_address', $machine->mac_address ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Subnet mask<input name="subnet_mask" value="{{ old('subnet_mask', $machine->subnet_mask ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Gateway<input name="gateway" value="{{ old('gateway', $machine->gateway ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">VLAN<input name="network_vlan" value="{{ old('network_vlan', $machine->network_vlan ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Primary DNS<input name="primary_dns" value="{{ old('primary_dns', $machine->primary_dns ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">Secondary DNS<input name="secondary_dns" value="{{ old('secondary_dns', $machine->secondary_dns ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">SNMP version<input name="snmp_version" value="{{ old('snmp_version', $machine->snmp_version ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="block text-sm font-semibold text-slate-700">SNMP community<input name="snmp_community" value="{{ old('snmp_community', $machine->snmp_community ?? '') }}" class="mt-2 w-full rounded-lg border-zinc-300 px-3 py-2.5"></label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="dhcp_enabled" value="1" @checked(old('dhcp_enabled', $machine->dhcp_enabled ?? false))> DHCP enabled</label>
        </div>
        <label class="mt-4 block text-sm font-semibold text-slate-700">Network notes
            <textarea name="network_notes" class="mt-2 h-24 w-full rounded-lg border-zinc-300 px-3 py-2.5">{{ old('network_notes', $machine->network_notes ?? '') }}</textarea>
        </label>
    </section>

</div>

@if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<button class="app-button mt-6">Save machine</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const manufacturer = document.querySelector('[data-machine-manufacturer]');
        const model = document.querySelector('[data-machine-model]');

        if (! manufacturer || ! model) {
            return;
        }

        const syncModels = () => {
            const selectedManufacturer = manufacturer.value;
            let selectedOptionVisible = false;

            [...model.options].forEach((option) => {
                if (! option.value) {
                    option.hidden = false;
                    return;
                }

                const visible = selectedManufacturer && option.dataset.manufacturerId === selectedManufacturer;
                option.hidden = ! visible;

                if (visible && option.selected) {
                    selectedOptionVisible = true;
                }
            });

            if (! selectedOptionVisible) {
                model.value = '';
            }

            model.disabled = ! selectedManufacturer;
        };

        manufacturer.addEventListener('change', syncModels);
        syncModels();
    });
</script>
