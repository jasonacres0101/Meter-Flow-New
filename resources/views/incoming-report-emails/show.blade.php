<x-layouts.app :title="$email->subject">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="app-page-title">{{ $email->subject ?: 'Incoming email' }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $email->from_email }} / {{ $email->customerStatusLabel() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->isPlatformAdmin() && in_array($email->parse_status, [\App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE, \App\Models\IncomingReportEmail::STATUS_UNMATCHED], true))
                <a href="{{ route('report-templates.create', ['incoming_report_email_id' => $email->id]) }}" class="app-button">Build template from email</a>
            @endif
            @if(auth()->user()->isPlatformAdmin())
                <form method="post" action="{{ route('incoming-report-emails.reprocess', $email) }}">@csrf<button class="app-button-secondary">Reprocess</button></form>
            @endif
        </div>
    </div>

    @if($email->parse_status === \App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE)
        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Setup in progress.</strong>
            We have received this report and support will finish setup for this report format.
        </div>
    @endif
    @if($email->parse_status === \App\Models\IncomingReportEmail::STATUS_UNMATCHED)
        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Waiting for machine match.</strong>
            We could not match this report to a machine yet. Check that the machine serial number has been added correctly.
        </div>
    @endif
    @if(auth()->user()->isPlatformAdmin() && $email->parse_error)<div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $email->parse_error }}</div>@endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Raw Email</h2>
            <pre class="mt-4 max-h-[42rem] overflow-auto rounded-lg bg-slate-950 p-4 text-sm leading-6 text-slate-100">{{ $email->body_text }}</pre>
        </section>
        @if(auth()->user()->isPlatformAdmin())
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Parsed Payload</h2>
                <pre class="mt-4 max-h-[42rem] overflow-auto rounded-lg bg-slate-50 p-4 text-sm">{{ json_encode($email->parsed_payload, JSON_PRETTY_PRINT) }}</pre>
            </section>
        @else
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Report Status</h2>
                <div class="mt-4 rounded-lg bg-slate-50 p-4">
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $email->customerStatusTone() }}">{{ $email->customerStatusLabel() }}</span>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Support setup is handled by the SaaS team when a report format is new. Once complete, readings will appear automatically on the machine page.</p>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
