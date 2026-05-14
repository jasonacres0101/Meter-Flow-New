<x-layouts.app title="Users">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="app-page-title">Users</h1>
            <p class="text-sm text-slate-500">Manage office users and engineers.</p>
        </div>
        <a href="{{ route('users.create') }}" class="app-button">Add user</a>
    </div>

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                    </td>
                    <td>{{ $user->isEngineer() ? $user->engineerCompanies->pluck('name')->join(', ') : ($user->company?->name ?? 'Platform') }}</td>
                    <td>{{ str_replace('_', ' ', $user->role) }}</td>
                    <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        @if(auth()->user()->isPlatformAdmin() && ! $user->isPlatformAdmin() && $user->is_active)
                            <form method="post" action="{{ route('users.impersonate', $user) }}">
                                @csrf
                                <button class="app-button-secondary">Login as</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
