<x-layouts.app title="Machines">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="app-page-title">Machines</h1>
        <a href="{{ route('machines.create') }}" class="app-button">Add machine</a>
    </div>
    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th>Machine</th><th>Client</th><th>Site</th><th>Serial</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($machines as $machine)
                <tr>
                    <td><a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('machines.show', $machine) }}">{{ $machine->machine_name ?? $machine->model }}</a><div class="text-xs text-slate-500">{{ $machine->manufacturer }} {{ $machine->model }}</div></td>
                    <td>{{ $machine->client->name }}</td><td>{{ $machine->site->name }}</td><td>{{ $machine->serial_number }}</td><td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $machine->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $machine->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $machines->links() }}</div>
</x-layouts.app>
