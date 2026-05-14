<x-layouts.app title="Clients">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="app-page-title">Clients</h1>
            <p class="mt-1 text-sm text-slate-500">Manage customer accounts, then add their sites and machines.</p>
        </div>
        @unless(auth()->user()->isEngineer())
            <a href="{{ route('clients.create') }}" class="app-button">Add client</a>
        @endunless
    </div>

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Sites</th>
                    <th>Machines</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('clients.show', $client) }}">{{ $client->name }}</a>
                            <div class="text-xs text-slate-500">{{ $client->account_reference ?: 'No account reference' }}</div>
                        </td>
                        <td>{{ $client->sites_count }} sites</td>
                        <td>{{ $client->machines_count }} machines</td>
                        <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $client->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $client->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('clients.show', $client) }}" class="app-button-secondary">View</a>
                                @unless(auth()->user()->isEngineer())
                                    <a href="{{ route('clients.edit', $client) }}" class="app-button-secondary">Edit</a>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">No clients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>
</x-layouts.app>
