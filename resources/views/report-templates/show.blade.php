<x-layouts.app :title="$reportTemplate->displayName()">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">{{ $reportTemplate->machineModel->manufacturer }} {{ $reportTemplate->machineModel->model_name }}</div>
            <h1 class="app-page-title mt-2">{{ $reportTemplate->displayName() }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $reportTemplate->company?->name ?? 'Platform master' }} / {{ $reportTemplate->parser_type }}</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->isPlatformAdmin() && $reportTemplate->company_id && $reportTemplate->approval_status === \App\Models\ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW)
                <form method="post" action="{{ route('report-templates.approve-global', $reportTemplate) }}">
                    @csrf
                    <button class="app-button">Approve global version</button>
                </form>
            @endif
            <form method="post" action="{{ route('report-templates.duplicate', $reportTemplate) }}">
                @csrf
                <button class="app-button-secondary">Clone template</button>
            </form>
            @if(auth()->user()->isPlatformAdmin() || ! is_null($reportTemplate->company_id))
                <a href="{{ route('report-templates.edit', $reportTemplate) }}" class="app-button">Edit</a>
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Template Details</h2>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Subject sample</dt><dd class="mt-1 font-semibold text-slate-950">{{ $reportTemplate->sample_subject ?: 'Not set' }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Parser type</dt><dd class="mt-1 font-mono text-xs text-slate-950">{{ $reportTemplate->parser_type }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Version</dt><dd class="mt-1 font-semibold text-slate-950">v{{ $reportTemplate->version }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Approval</dt><dd class="mt-1 font-semibold text-slate-950">{{ str($reportTemplate->approval_status)->replace('_', ' ')->title() }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Status</dt><dd class="mt-1 font-semibold text-slate-950">{{ $reportTemplate->is_active ? 'Active' : 'Inactive' }}</dd></div>
            </dl>
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Sample Body</h2>
            <pre class="mt-4 max-h-[34rem] overflow-auto rounded-lg bg-slate-950 p-4 text-sm leading-6 text-slate-100">{{ $reportTemplate->sample_body }}</pre>
        </section>
    </div>
</x-layouts.app>
