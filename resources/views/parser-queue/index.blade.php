<x-layouts.app title="Parser Queue">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="app-page-title">Parser Queue</h1>
            <p class="mt-1 text-sm text-slate-500">Support review for matched reports that need parser setup, plus separate triage for unmatched and failed reports.</p>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach([
            'ready' => 'Ready for template',
            'machine-match' => 'Needs machine match',
            'failed' => 'Failed parse',
            'all' => 'All review',
        ] as $key => $label)
            <a href="{{ route('parser-queue.index', ['bucket' => $key]) }}" class="rounded-lg border px-3 py-2 text-sm font-black transition {{ $bucket === $key ? 'border-teal-300 bg-teal-50 text-teal-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-xs text-slate-500">{{ $bucketCounts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <form method="get" class="app-panel mb-5 grid gap-3 rounded-xl p-4 lg:grid-cols-[1fr_0.45fr_auto]">
        <input type="hidden" name="bucket" value="{{ $bucket }}">
        <label class="app-field">
            Search
            <input name="q" value="{{ request('q') }}" placeholder="Company, serial, model, subject or sender" class="app-field-control">
        </label>
        <label class="app-field">
            Status
            <select name="status" class="app-field-control">
                <option value="">Needs review</option>
                @foreach([
                    \App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE => 'Setup in progress',
                    \App\Models\IncomingReportEmail::STATUS_FAILED => 'Failed parse',
                    \App\Models\IncomingReportEmail::STATUS_UNMATCHED => 'Unmatched',
                ] as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="app-button h-11">Filter</button>
            <a href="{{ route('parser-queue.index', ['bucket' => $bucket]) }}" class="app-button-secondary h-11">Reset</a>
        </div>
    </form>

    @if($bucket === 'machine-match')
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Machine match first.</strong>
            These reports cannot be approved as templates yet because the app does not know which machine model they belong to.
        </div>
    @elseif($bucket === 'ready')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <strong>Ready for approval.</strong>
            These reports already match a machine, so AI mapping and template approval can be completed here.
        </div>
    @endif

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Account</th>
                    <th>Machine</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                    @php($aiRecommendation = $email->ai_review_recommendation)
                    <tr>
                        <td>
                            <div class="font-bold text-slate-950">{{ $email->subject ?: 'No subject' }}</div>
                            <div class="text-xs text-slate-500">{{ $email->from_email ?: 'Unknown sender' }}</div>
                            <div class="mt-2 inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold {{ $aiRecommendation['tone'] }}">
                                <span>{{ $aiRecommendation['label'] }}</span>
                                <span>{{ $aiRecommendation['confidence_label'] }} local confidence</span>
                            </div>
                        </td>
                        <td>{{ $email->company?->name ?? $email->machine?->client?->company?->name ?? 'Unknown' }}</td>
                        <td>
                            @if($email->machine)
                                <div class="font-bold text-slate-950">{{ $email->machine->serial_number }}</div>
                                <div class="text-xs text-slate-500">{{ $email->machine->manufacturer }} {{ $email->machine->model }}</div>
                            @else
                                <div class="font-bold text-amber-800">No machine match</div>
                                <div class="text-xs text-slate-500">Detected {{ $email->extractedSerialNumber() ?: 'no serial' }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $email->customerStatusTone() }}">{{ $email->customerStatusLabel() }}</span>
                            <div class="mt-1 font-mono text-xs text-slate-400">{{ $email->parse_status }}</div>
                        </td>
                        <td>{{ $email->received_at?->format('d M Y H:i') ?? 'Unknown' }}</td>
                        <td class="text-right">
                            <a href="{{ route('parser-queue.show', $email) }}" class="app-button-secondary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-slate-500">No reports currently need parser review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $emails->links() }}</div>
</x-layouts.app>
