<x-layouts.app :title="$email->subject">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="app-page-title">{{ $email->subject ?: 'Incoming email' }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $email->from_email }} / {{ str_replace('_', ' ', $email->parse_status) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(in_array($email->parse_status, [\App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE, \App\Models\IncomingReportEmail::STATUS_UNMATCHED], true))
                <a href="{{ route('report-templates.create', ['incoming_report_email_id' => $email->id]) }}" class="app-button">Build template from email</a>
            @endif
            <form method="post" action="{{ route('incoming-report-emails.reprocess', $email) }}">@csrf<button class="app-button-secondary">Reprocess</button></form>
        </div>
    </div>

    @if($email->parse_status === \App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE)
        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Template needed.</strong>
            This email matched machine {{ $email->machine?->serial_number }}, but no active template exists for {{ $email->machine?->manufacturer }} {{ $email->machine?->model }}.
        </div>
    @endif
    @if($email->parse_status === \App\Models\IncomingReportEmail::STATUS_UNMATCHED)
        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            <strong>No machine matched.</strong>
            The detected serial is <span class="font-mono font-black">{{ $email->extractedSerialNumber() ?: 'not found' }}</span>. Create or update a machine with this exact serial number, then reprocess this email.
        </div>
    @endif
    @if($email->parse_error)<div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $email->parse_error }}</div>@endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Raw Email</h2>
            <pre class="mt-4 max-h-[42rem] overflow-auto rounded-lg bg-slate-950 p-4 text-sm leading-6 text-slate-100">{{ $email->body_text }}</pre>
        </section>
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black">Parsed Payload</h2>
            <pre class="mt-4 max-h-[42rem] overflow-auto rounded-lg bg-slate-50 p-4 text-sm">{{ json_encode($email->parsed_payload, JSON_PRETTY_PRINT) }}</pre>
        </section>
    </div>
</x-layouts.app>
