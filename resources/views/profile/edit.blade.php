<x-layouts.app title="Profile">
    <div class="mb-6">
        <h1 class="app-page-title">Profile</h1>
        <p class="mt-1 text-sm text-slate-500">Update your user details and password.</p>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('PUT')
        <section class="app-panel max-w-3xl rounded-xl p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="app-field">Name
                    <input name="name" value="{{ old('name', $user->name) }}" class="app-field-control">
                </label>
                <label class="app-field">Email
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="app-field-control">
                </label>
                <label class="app-field">New password
                    <input name="password" type="password" class="app-field-control" placeholder="Leave blank to keep current password">
                </label>
                <label class="app-field">Confirm password
                    <input name="password_confirmation" type="password" class="app-field-control">
                </label>
            </div>
        </section>

        @if($user->isEngineer())
            @php($profile = $user->engineerSkillProfile)
            @php($supported = $user->supportedManufacturers->keyBy('id'))
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Engineer Skill Profile</h2>
                <p class="mt-1 text-sm text-slate-500">Set the networking areas you are comfortable supporting. These skills can be used to match tickets to the right subcontractor.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach([
                        'networking_level' => 'Networking',
                        'vlan_level' => 'VLANs',
                        'dhcp_static_ip_level' => 'DHCP / static IPs',
                        'dns_level' => 'DNS',
                        'routing_level' => 'Routing',
                        'firewall_level' => 'Firewall',
                    ] as $field => $label)
                        <label class="app-field">{{ $label }}
                            <select name="skills[{{ $field }}]" class="app-field-control">
                                @foreach($skillLevels as $value => $text)
                                    <option value="{{ $value }}" @selected(old('skills.'.$field, $profile?->{$field} ?? 'none') === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <label class="app-field mt-4">Engineer notes
                    <textarea name="skills[notes]" class="app-field-control h-24" placeholder="Specialist areas, regions covered or limitations">{{ old('skills.notes', $profile?->notes) }}</textarea>
                </label>
            </section>

            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Supported Manufacturers</h2>
                <p class="mt-1 text-sm text-slate-500">Choose every manufacturer you support and whether your skill level is basic or advanced.</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($manufacturers as $manufacturer)
                        <label class="rounded-lg border border-slate-200 bg-white p-3 text-sm font-bold text-slate-700">
                            <span class="block text-slate-950">{{ $manufacturer->name }}</span>
                            <select name="manufacturer_skills[{{ $manufacturer->id }}]" class="mt-2">
                                <option value="">Not supported</option>
                                @foreach($skillLevels as $value => $text)
                                    <option value="{{ $value }}" @selected(old('manufacturer_skills.'.$manufacturer->id, $supported->get($manufacturer->id)?->pivot?->skill_level) === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <button class="app-button mt-5">Save profile</button>
    </form>
</x-layouts.app>
