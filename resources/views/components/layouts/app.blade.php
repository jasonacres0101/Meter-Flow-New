<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Copier Monitor' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-950 antialiased">
    <div class="min-h-screen lg:flex">
        @auth
            @php
                $activeCompanyName = auth()->user()->isEngineer()
                    ? \App\Support\Tenant::accessibleCompanies(auth()->user())->firstWhere('id', \App\Support\Tenant::activeCompanyId(auth()->user()))?->name
                    : (auth()->user()->company?->name ?? 'Platform admin');
                $homeRoute = auth()->user()->isPlatformAdmin() ? 'companies.index' : 'dashboard';
                $operationsNav = auth()->user()->isPlatformAdmin()
                    ? [
                        ['label' => 'Accounts', 'route' => 'companies.index', 'show' => true],
                        ['label' => 'Billing', 'route' => 'billing.show', 'show' => true],
                        ['label' => 'Users', 'route' => 'users.index', 'show' => true],
                        ['label' => 'Parser Library', 'route' => 'parser-definitions.index', 'show' => true],
                        ['label' => 'Parser Queue', 'route' => 'parser-queue.index', 'show' => true],
                        ['label' => 'Master Models', 'route' => 'machine-models.index', 'show' => true],
                        ['label' => 'Report Templates', 'route' => 'report-templates.index', 'show' => true],
                        ['label' => 'Master Mailbox', 'route' => 'email-sources.index', 'show' => true],
                        ['label' => 'AI Settings', 'route' => 'platform-ai-settings.edit', 'show' => true],
                        ['label' => 'Outbound Email', 'route' => 'platform-mail-settings.edit', 'show' => true],
                    ]
                    : [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'show' => true],
                        ['label' => 'Users', 'route' => 'users.index', 'show' => auth()->user()->canManageCompanyUsers()],
                        ['label' => 'Machines', 'route' => 'machines.index', 'show' => true],
                        ['label' => 'Service Tickets', 'route' => 'service-tickets.index', 'show' => true],
                        ['label' => 'Models', 'route' => 'machine-models.index', 'show' => ! auth()->user()->isEngineer()],
                        ['label' => 'Clients', 'route' => 'clients.index', 'show' => true],
                        ['label' => 'Sites', 'route' => 'sites.index', 'show' => true],
                        ['label' => 'Site Map', 'route' => 'sites.map', 'show' => true],
                        ['label' => 'Stock', 'route' => 'stock.index', 'show' => ! auth()->user()->isEngineer()],
                        ['label' => 'Revenue Reports', 'route' => 'reports.revenue', 'show' => ! auth()->user()->isEngineer()],
                    ];
                $settingsNav = auth()->user()->isPlatformAdmin()
                    ? []
                    : [
                        ['label' => 'Setup Help', 'route' => 'settings-help.show', 'show' => true],
                        ['label' => 'Email Sources', 'route' => 'email-sources.index', 'show' => ! auth()->user()->isEngineer()],
                        ['label' => 'Billing', 'route' => 'company-billing.show', 'show' => auth()->user()->isCompanyAdmin()],
                        ['label' => 'Service Agreements', 'route' => 'service-agreements.index', 'show' => auth()->user()->canManageCompanyUsers()],
                        ['label' => 'Pricing', 'route' => 'pricing-settings.edit', 'show' => auth()->user()->canManageCompanyUsers()],
                        ['label' => 'Toner Alerts', 'route' => 'toner-alert-settings.edit', 'show' => auth()->user()->canManageCompanyUsers()],
                        ['label' => 'Report Emails', 'route' => 'incoming-report-emails.index', 'show' => ! auth()->user()->isEngineer()],
                    ];
            @endphp
            <aside class="hidden w-72 shrink-0 border-r border-slate-900 bg-slate-950 text-white shadow-2xl shadow-slate-950/20 lg:block">
                <div class="flex h-full flex-col p-5">
                    <a href="{{ route($homeRoute) }}" class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-gradient-to-br from-teal-300 to-cyan-400 text-lg font-black text-slate-950 shadow-lg shadow-teal-950/30">CM</span>
                        <span><span class="block text-lg font-black">Copier Monitor</span><span class="text-xs font-semibold text-slate-400">SaaS operations console</span></span>
                    </a>
                    @if(auth()->user()->isEngineer())
                        <form method="post" action="{{ route('active-company.update') }}" class="mt-6 rounded-xl border border-teal-300/20 bg-teal-400/10 p-4 shadow-sm">
                            @csrf
                            @method('PUT')
                            <label class="text-xs font-bold uppercase tracking-wide text-teal-200">Active company
                                <select name="company_id" onchange="this.form.submit()" class="mt-2 w-full rounded-lg border-white/10 bg-slate-900 px-3 py-2.5 text-sm font-bold text-white outline-none ring-0 transition focus:border-teal-300">
                                    @foreach(\App\Support\Tenant::accessibleCompanies(auth()->user()) as $company)
                                        <option value="{{ $company->id }}" @selected(\App\Support\Tenant::activeCompanyId(auth()->user()) === $company->id)>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="mt-2 text-xs text-teal-100/75">Switch company context before viewing tickets, machines or sites.</p>
                        </form>
                    @endif
                    <nav class="mt-8 space-y-6">
                        <div>
                            <div class="px-3 text-xs font-bold uppercase tracking-wide text-slate-500">Operations</div>
                            <div class="mt-2 space-y-1">
                        @foreach($operationsNav as $item)
                            @if($item['show'])
                                @php($isActive = request()->routeIs($item['route']) || request()->routeIs(str($item['route'])->beforeLast('.')->toString().'.*'))
                                <a href="{{ route($item['route']) }}" class="group flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $isActive ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-teal-500' : 'bg-slate-700 group-hover:bg-teal-300' }}"></span>
                                </a>
                            @endif
                        @endforeach
                            </div>
                        </div>

                        @if(count($settingsNav))
                        <div>
                            <div class="px-3 text-xs font-bold uppercase tracking-wide text-slate-500">Settings</div>
                            <div class="mt-2 space-y-1">
                        @foreach($settingsNav as $item)
                            @if($item['show'])
                                @php($isActive = request()->routeIs($item['route']) || request()->routeIs(str($item['route'])->beforeLast('.')->toString().'.*'))
                                <a href="{{ route($item['route']) }}" class="group flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $isActive ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-blue-500' : 'bg-slate-700 group-hover:bg-blue-300' }}"></span>
                                </a>
                            @endif
                        @endforeach
                            </div>
                        </div>
                        @endif
                    </nav>
                    <div class="mt-auto rounded-lg border border-white/10 bg-white/5 p-4 shadow-inner shadow-white/5">
                        <details class="group relative">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-md px-1 py-1">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="mt-1 block truncate text-xs text-slate-400">{{ $activeCompanyName }}</span>
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-white/10 bg-white/10 text-slate-200 transition group-open:bg-teal-400 group-open:text-slate-950" title="Account menu">
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z" />
                                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.04.04a2 2 0 0 1-2.83 2.83l-.04-.04A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6V20a2 2 0 0 1-4 0v-.05a1.7 1.7 0 0 0-1-.55 1.7 1.7 0 0 0-1.88.34l-.04.04a2 2 0 0 1-2.83-2.83l.04-.04A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1H4a2 2 0 0 1 0-4h.05a1.7 1.7 0 0 0 .55-1 1.7 1.7 0 0 0-.34-1.88l-.04-.04a2 2 0 0 1 2.83-2.83l.04.04A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6V4a2 2 0 0 1 4 0v.05a1.7 1.7 0 0 0 1 .55 1.7 1.7 0 0 0 1.88-.34l.04-.04a2 2 0 0 1 2.83 2.83l-.04.04A1.7 1.7 0 0 0 19.4 9c.22.35.42.69.6 1H20a2 2 0 0 1 0 4h-.05a1.7 1.7 0 0 0-.55 1Z" />
                                    </svg>
                                </span>
                            </summary>
                            <div class="mt-3 space-y-1 rounded-lg border border-white/10 bg-slate-900 p-2 shadow-xl">
                                <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white">Profile</a>
                                <form method="post" action="{{ route('logout') }}" class="pt-1">
                                    @csrf
                                    <button class="w-full rounded-md bg-white px-3 py-2 text-left text-sm font-semibold text-slate-950">Logout</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            </aside>
        @endauth

        <div class="min-w-0 flex-1">
            @if(session('impersonator_user_id'))
                <div class="border-b border-amber-200 bg-amber-50">
                    <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <div>
                            <span class="font-bold">Support mode:</span>
                            logged in as {{ session('impersonated_user_name', auth()->user()->name) }}
                            from {{ session('impersonator_name', 'SaaS admin') }}.
                        </div>
                        <form method="post" action="{{ route('impersonation.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-bold text-amber-900 shadow-sm">Return to SaaS admin</button>
                        </form>
                    </div>
                </div>
            @endif
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-teal-700">{{ auth()->check() ? $activeCompanyName : 'Platform' }}</div>
                        <div class="text-xl font-black text-slate-950">{{ $title ?? 'Copier Monitor' }}</div>
                    </div>
                    @auth
                        <div class="flex items-center gap-3 lg:hidden">
                            @if(auth()->user()->isEngineer())
                                <form method="post" action="{{ route('active-company.update') }}" class="hidden sm:block">
                                    @csrf
                                    @method('PUT')
                                    <select aria-label="Active company" name="company_id" onchange="this.form.submit()" class="max-w-48 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm">
                                        @foreach(\App\Support\Tenant::accessibleCompanies(auth()->user()) as $company)
                                            <option value="{{ $company->id }}" @selected(\App\Support\Tenant::activeCompanyId(auth()->user()) === $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                            <a href="{{ route($homeRoute) }}" class="rounded-md px-2 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ auth()->user()->isPlatformAdmin() ? 'Accounts' : 'Dashboard' }}</a>
                            <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-md px-2 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Logout</button></form>
                        </div>
                    @endauth
                </div>
            </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800 shadow-sm">{{ $errors->first() }}</div>
            @endif
            {{ $slot }}
        </main>
        </div>
    </div>
</body>
</html>
