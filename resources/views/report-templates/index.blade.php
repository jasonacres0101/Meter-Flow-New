<x-layouts.app title="Report Templates">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="app-page-title">Report Templates</h1>
            <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->isPlatformAdmin() ? 'Build and maintain reusable prebuilt parser examples for the master library.' : 'Search prebuilt templates, clone useful examples, and maintain your company templates.' }}</p>
        </div>
        <a href="{{ route('report-templates.create') }}" class="app-button">Add template</a>
    </div>

    <form method="get" class="app-panel mb-5 grid gap-3 rounded-xl p-4 lg:grid-cols-[1.2fr_0.8fr_0.6fr_0.6fr_auto]">
        <label class="app-field">
            Search
            <input name="q" value="{{ request('q') }}" placeholder="Template, subject, make or model" class="app-field-control">
        </label>
        <label class="app-field">
            Parser
            <select name="parser_type" class="app-field-control">
                <option value="">All parsers</option>
                @foreach($parserTypes as $parser => $label)
                    <option value="{{ $parser }}" @selected(request('parser_type') === $parser)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="app-field">
            Status
            <select name="status" class="app-field-control">
                <option value="">Any</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </label>
        <label class="app-field">
            Owner
            <select name="owner" class="app-field-control">
                <option value="">All</option>
                <option value="prebuilt" @selected(request('owner') === 'prebuilt')>Prebuilt</option>
                @if(auth()->user()->isPlatformAdmin())
                    <option value="pending" @selected(request('owner') === 'pending')>Pending approval</option>
                @endif
                @unless(auth()->user()->isPlatformAdmin())
                    <option value="company" @selected(request('owner') === 'company')>Company</option>
                @endunless
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="app-button h-11">Filter</button>
            <a href="{{ route('report-templates.index') }}" class="app-button-secondary h-11">Reset</a>
        </div>
    </form>

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Parser</th>
                    <th>Status</th>
                    <th>Owner</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportTemplates as $template)
                    <tr>
                        <td>
                            <a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('report-templates.show', $template) }}">{{ $template->displayName() }}</a>
                            <div class="text-xs text-slate-500">{{ $template->machineModel->manufacturer }} {{ $template->machineModel->model_name }} / {{ $template->sample_subject ?: 'No subject sample' }}</div>
                        </td>
                        <td><span class="font-mono text-xs text-slate-700">{{ $template->parser_type }}</span></td>
                        <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $template->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            @if(is_null($template->company_id))
                                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">Prebuilt</span>
                            @elseif($template->approval_status === \App\Models\ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Pending global approval</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $template->company?->name ?? 'Company' }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('report-templates.show', $template) }}" class="app-button-secondary">View</a>
                                <form method="post" action="{{ route('report-templates.duplicate', $template) }}">
                                    @csrf
                                    <button class="app-button-secondary">Clone</button>
                                </form>
                                @if(auth()->user()->isPlatformAdmin() && $template->company_id && $template->approval_status === \App\Models\ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW)
                                    <form method="post" action="{{ route('report-templates.approve-global', $template) }}">
                                        @csrf
                                        <button class="app-button">Approve global</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">No templates match those filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reportTemplates->links() }}</div>
</x-layouts.app>
