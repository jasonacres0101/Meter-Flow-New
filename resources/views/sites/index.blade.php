<x-layouts.app title="Sites">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="app-page-title">Sites</h1>
            <p class="mt-1 text-sm text-slate-500">Manage customer locations and view them on the map.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sites.map') }}" class="app-button-secondary">View map</a>
            @unless(auth()->user()->isEngineer())
                <a href="{{ route('sites.create') }}" class="app-button">Add site</a>
            @endunless
        </div>
    </div>

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Client</th>
                    <th>Town</th>
                    <th>Machines</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    <tr>
                        <td>
                            <a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('sites.show', $site) }}">{{ $site->name }}</a>
                            <div class="text-xs text-slate-500">{{ $site->postcode ?: 'No postcode' }}</div>
                        </td>
                        <td>{{ $site->client->name }}</td>
                        <td>{{ $site->city ?: '-' }}</td>
                        <td>{{ $site->machines_count }} machines</td>
                        <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $site->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $site->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('sites.show', $site) }}" class="app-button-secondary">View</a>
                                @unless(auth()->user()->isEngineer())
                                    <a href="{{ route('sites.edit', $site) }}" class="app-button-secondary">Edit</a>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-slate-500">No sites yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $sites->links() }}</div>
</x-layouts.app>
