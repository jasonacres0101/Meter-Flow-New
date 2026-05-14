<x-layouts.app title="Service Agreement">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Commercial agreements</div>
            <h1 class="mt-1 text-2xl font-black text-slate-950">{{ $agreement->agreement_number }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Machine-owned agreement covering {{ $agreement->machines->count() }} machine{{ $agreement->machines->count() === 1 ? '' : 's' }}.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('service-agreements.index') }}" class="app-button-secondary">All agreements</a>
            <a href="{{ route('service-agreements.edit', $agreement) }}" class="app-button">Edit agreement</a>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_22rem]">
        <section class="app-panel p-5">
            <h2 class="text-lg font-black text-slate-950">Agreement Terms</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start date</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $agreement->starts_on?->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">End date</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $agreement->ends_on?->format('d M Y') ?? 'Open ended' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">B/W PPC</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ number_format((float) $agreement->mono_ppc, 3) }}p</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Colour PPC</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ number_format((float) $agreement->colour_ppc, 3) }}p</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Included B/W copies</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ number_format((int) $agreement->included_mono_pages) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Included colour copies</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ number_format((int) $agreement->included_colour_pages) }}</dd>
                </div>
            </dl>
        </section>

        <aside class="app-panel p-5">
            <h2 class="text-lg font-black text-slate-950">Status</h2>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Agreement state</div>
                <div class="mt-2 text-xl font-black {{ $agreement->is_active ? 'text-emerald-700' : 'text-slate-600' }}">{{ $agreement->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
            <form method="post" action="{{ route('service-agreements.destroy', $agreement) }}" class="mt-4" onsubmit="return confirm('Delete this service agreement?')">
                @csrf
                @method('DELETE')
                <button class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Delete agreement</button>
            </form>
        </aside>
    </div>

    <section class="app-panel mt-5 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-black text-slate-950">Covered Machines</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th>Machine</th>
                        <th>Client</th>
                        <th>Site</th>
                        <th>Serial</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agreement->machines as $machine)
                        <tr>
                            <td><a href="{{ route('machines.show', $machine) }}" class="font-bold text-teal-700 hover:text-teal-900">{{ $machine->machine_name }}</a></td>
                            <td>{{ $machine->client?->name }}</td>
                            <td>{{ $machine->site?->name }}</td>
                            <td class="font-mono text-xs">{{ $machine->serial_number }}</td>
                            <td>{{ $machine->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
