@if ($errors->any())
    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="app-panel p-5">
        <div class="grid gap-4 lg:grid-cols-4">
            <label class="app-field lg:col-span-2">Agreement number
                <input name="agreement_number" value="{{ old('agreement_number', $agreement?->agreement_number) }}" class="app-field-control" placeholder="SA-2026-001" required>
            </label>
            <label class="app-field">Start date
                <input name="starts_on" type="date" value="{{ old('starts_on', $agreement?->starts_on?->toDateString() ?? now()->toDateString()) }}" class="app-field-control" required>
            </label>
            <label class="app-field">End date
                <input name="ends_on" type="date" value="{{ old('ends_on', $agreement?->ends_on?->toDateString()) }}" class="app-field-control">
            </label>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-4">
            <label class="app-field">B/W PPC
                <input name="mono_ppc" type="number" min="0" step="0.001" value="{{ old('mono_ppc', $agreement?->mono_ppc) }}" class="app-field-control" placeholder="0.850">
            </label>
            <label class="app-field">Colour PPC
                <input name="colour_ppc" type="number" min="0" step="0.001" value="{{ old('colour_ppc', $agreement?->colour_ppc) }}" class="app-field-control" placeholder="4.950">
            </label>
            <label class="app-field">Included B/W copies
                <input name="included_mono_pages" type="number" min="0" step="1" value="{{ old('included_mono_pages', $agreement?->included_mono_pages) }}" class="app-field-control" placeholder="0">
            </label>
            <label class="app-field">Included colour copies
                <input name="included_colour_pages" type="number" min="0" step="1" value="{{ old('included_colour_pages', $agreement?->included_colour_pages) }}" class="app-field-control" placeholder="0">
            </label>
        </div>

        <label class="mt-5 flex items-center gap-3 text-sm font-bold text-slate-700">
            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $agreement?->is_active ?? true))>
            Active agreement
        </label>
    </section>

    <section class="app-panel overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <h2 class="text-lg font-black text-slate-950">Attach Machines</h2>
            <p class="mt-1 text-sm text-slate-500">Select every machine covered by this agreement. Machines can be added later without changing the client or site setup.</p>
        </div>
        <div class="max-h-[34rem] overflow-y-auto">
            <table class="app-table">
                <thead class="sticky top-0 bg-white text-xs uppercase tracking-wide text-slate-500 shadow-sm">
                    <tr>
                        <th class="w-12"></th>
                        <th>Machine</th>
                        <th>Client</th>
                        <th>Site</th>
                        <th>Serial</th>
                        <th>Current agreement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($machines as $machine)
                        @php($currentAgreement = $machine->agreements->first())
                        <tr>
                            <td>
                                <input name="machine_ids[]" type="checkbox" value="{{ $machine->id }}" @checked($selectedMachineIds->contains($machine->id))>
                            </td>
                            <td>
                                <div class="font-bold text-slate-950">{{ $machine->machine_name }}</div>
                                <div class="text-xs text-slate-500">{{ $machine->location ?: $machine->model }}</div>
                            </td>
                            <td>{{ $machine->client?->name }}</td>
                            <td>{{ $machine->site?->name }}</td>
                            <td class="font-mono text-xs">{{ $machine->serial_number }}</td>
                            <td class="text-xs text-slate-500">
                                @if ($currentAgreement && (! $agreement || $currentAgreement->id !== $agreement->id))
                                    <span class="font-mono">{{ $currentAgreement->agreement_number }}</span>
                                @elseif ($agreement && $currentAgreement?->id === $agreement->id)
                                    <span class="font-bold text-teal-700">This agreement</span>
                                @else
                                    No current agreement
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm text-slate-500">No machines available. Add machines before creating agreements.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="sticky bottom-4 flex justify-end gap-2">
        <a href="{{ route('service-agreements.index') }}" class="app-button-secondary bg-white shadow-lg">Cancel</a>
        <button class="app-button shadow-lg">Save agreement</button>
    </div>
</form>
