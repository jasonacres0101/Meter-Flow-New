<x-layouts.app title="Parser Queue">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="app-page-title">Parser Queue</h1>
            <p class="mt-1 text-sm text-slate-500">Support review for reports that need a machine match, parser setup, or template approval.</p>
        </div>
    </div>

    <form method="get" class="app-panel mb-5 grid gap-3 rounded-xl p-4 lg:grid-cols-[1fr_0.45fr_auto]">
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
            <a href="{{ route('parser-queue.index') }}" class="app-button-secondary h-11">Reset</a>
        </div>
    </form>

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
                    <tr>
                        <td>
                            <div class="font-bold text-slate-950">{{ $email->subject ?: 'No subject' }}</div>
                            <div class="text-xs text-slate-500">{{ $email->from_email ?: 'Unknown sender' }}</div>
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
