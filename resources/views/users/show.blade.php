<x-layouts.app :title="$managedUser->name">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="app-page-title">{{ $managedUser->name }}</h1>
            <p class="text-sm text-zinc-500">{{ $managedUser->email }} / {{ str_replace('_', ' ', $managedUser->role) }}</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->isPlatformAdmin() && ! $managedUser->isPlatformAdmin() && $managedUser->is_active)
                <form method="post" action="{{ route('users.impersonate', $managedUser) }}">
                    @csrf
                    <button class="app-button">Login as user</button>
                </form>
            @endif
            <a href="{{ route('users.edit', $managedUser) }}" class="app-button-secondary">Edit</a>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <x-stat label="Company" :value="$managedUser->isEngineer() ? $managedUser->engineerCompanies->pluck('name')->join(', ') : ($managedUser->company?->name ?? 'Platform')" />
        <x-stat label="Status" :value="$managedUser->is_active ? 'Active' : 'Inactive'" />
        <x-stat label="Engineer PIN" :value="$managedUser->hasEngineerPin() ? 'Set' : 'Not set'" />
    </div>
    @if($managedUser->isEngineer())
        <section class="app-panel mt-6 rounded-xl p-5">
            <h2 class="text-lg font-black">Engineer company access</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($managedUser->engineerCompanies as $company)
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-bold text-teal-800">{{ $company->name }}</span>
                @endforeach
            </div>
        </section>
        <section class="app-panel mt-6 rounded-xl p-5">
            <h2 class="text-lg font-black">Engineer skills</h2>
            @php($profile = $managedUser->engineerSkillProfile)
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach([
                    'networking_level' => 'Networking',
                    'vlan_level' => 'VLANs',
                    'dhcp_static_ip_level' => 'DHCP / static IPs',
                    'dns_level' => 'DNS',
                    'routing_level' => 'Routing',
                    'firewall_level' => 'Firewall',
                ] as $field => $label)
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                        <div class="mt-1 font-black text-slate-950">{{ ucfirst($profile?->{$field} ?? 'none') }}</div>
                    </div>
                @endforeach
            </div>
            @if($profile?->notes)
                <p class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-900">{{ $profile->notes }}</p>
            @endif
        </section>
        <section class="app-panel mt-6 rounded-xl p-5">
            <h2 class="text-lg font-black">Supported manufacturers</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse($managedUser->supportedManufacturers as $manufacturer)
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-bold text-teal-800">{{ $manufacturer->name }} / {{ ucfirst($manufacturer->pivot->skill_level) }}</span>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No supported manufacturers have been added.</p>
                @endforelse
            </div>
        </section>
    @endif
</x-layouts.app>
