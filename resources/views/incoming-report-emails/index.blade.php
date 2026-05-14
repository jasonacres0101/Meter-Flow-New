<x-layouts.app title="Incoming Emails">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="app-page-title">Incoming Emails</h1>
            <p class="mt-1 text-sm text-slate-500">Raw emails are retained permanently. Matched reports without a template are held for setup.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="{{ route('incoming-report-emails.pull') }}">
                @csrf
                <button class="app-button-secondary">Pull emails</button>
            </form>
            <a href="{{ route('incoming-report-emails.create') }}" class="app-button">Add sample email</a>
        </div>
    </div>
    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <tbody>
                @foreach($emails as $email)
                    <tr>
                        <td>
                            <a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('incoming-report-emails.show', $email) }}">{{ $email->subject ?: 'No subject' }}</a>
                            <div class="text-xs text-slate-500">{{ $email->from_email }}</div>
                        </td>
                        <td>
                            @if($email->machine)
                                <span class="font-bold text-slate-900">{{ $email->machine->serial_number }}</span>
                            @else
                                <span class="font-bold text-amber-800">Unmatched</span>
                                <span class="mt-1 block text-xs text-slate-500">Detected: {{ $email->extractedSerialNumber() ?: 'No serial found' }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $email->parse_status === \App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE ? 'bg-amber-50 text-amber-800' : ($email->parse_status === \App\Models\IncomingReportEmail::STATUS_PARSED ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">{{ str_replace('_', ' ', $email->parse_status) }}</span>
                        </td>
                        <td>{{ $email->received_at->format('d M H:i') }}</td>
                        <td class="text-right">
                            @if(in_array($email->parse_status, [\App\Models\IncomingReportEmail::STATUS_PENDING_TEMPLATE, \App\Models\IncomingReportEmail::STATUS_UNMATCHED], true))
                                <a href="{{ route('report-templates.create', ['incoming_report_email_id' => $email->id]) }}" class="app-button-secondary">Build template</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $emails->links() }}</div>
</x-layouts.app>
