<x-layouts.app :title="$email->subject ?: 'Parser Review'">
    @php
        $configurationJson = json_encode($suggestedConfiguration, JSON_PRETTY_PRINT);
        $canApprove = filled($email->machine_id);
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Parser review</div>
            <h1 class="app-page-title mt-2">{{ $email->subject ?: 'Incoming report' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $email->company?->name ?? 'Unknown account' }} / {{ $email->from_email ?: 'Unknown sender' }}</p>
        </div>
        <a href="{{ route('parser-queue.index') }}" class="app-button-secondary">Back to queue</a>
    </div>

    @unless($canApprove)
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Machine match needed.</strong>
            The detected serial is <span class="font-mono font-black">{{ $email->extractedSerialNumber() ?: 'not found' }}</span>. Create or update the machine first, then reprocess this email.
        </div>
    @endunless

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <section class="space-y-5">
            <div class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Report Summary</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Status</dt><dd class="mt-1 font-semibold text-slate-950">{{ $email->customerStatusLabel() }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Technical status</dt><dd class="mt-1 font-mono text-xs text-slate-950">{{ $email->parse_status }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Serial</dt><dd class="mt-1 font-semibold text-slate-950">{{ $email->machine?->serial_number ?? $email->extractedSerialNumber() ?? 'Not found' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="font-bold text-slate-500">Model</dt><dd class="mt-1 font-semibold text-slate-950">{{ $email->machine ? $email->machine->manufacturer.' '.$email->machine->model : 'Not matched' }}</dd></div>
                </dl>
                @if($email->parse_error)
                    <div class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $email->parse_error }}</div>
                @endif
            </div>

            <div class="app-panel rounded-xl p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-slate-950">Detected Fields</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $detectedFields->count() }}</span>
                </div>
                <div class="mt-4 grid max-h-96 gap-2 overflow-auto pr-1 sm:grid-cols-2">
                    @forelse($detectedFields as $field)
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="text-sm font-black text-slate-950">{{ $field['label'] }}</div>
                            <div class="mt-1 truncate font-mono text-xs text-slate-500">{{ $field['value'] }}</div>
                        </div>
                    @empty
                        <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No fields were detected automatically. Review the raw email and build the mapping manually.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="space-y-5">
            <form method="post" action="{{ route('parser-queue.approve-company', $email) }}" class="app-panel rounded-xl p-5">
                @csrf
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Suggested Template</h2>
                        <p class="mt-1 text-sm text-slate-500">Review the mapping, then approve it for this account or promote it to the global parser library.</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">Draft</span>
                </div>

                <label class="app-field mt-4">Parser type
                    <select name="parser_type" class="app-field-control">
                        @foreach($parserTypes as $parser => $label)
                            <option value="{{ $parser }}" @selected($suggestedParserType === $parser)>{{ $label }} / {{ $parser }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="app-field mt-4">Parser mapping JSON
                    <textarea name="parser_configuration" class="app-field-control h-72 font-mono text-xs">{{ $configurationJson }}</textarea>
                </label>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <button class="app-button" @disabled(! $canApprove)>Approve for this account</button>
                    <button
                        formaction="{{ route('parser-queue.approve-global', $email) }}"
                        class="app-button-secondary"
                        @disabled(! $canApprove)
                    >Approve as global template</button>
                </div>
            </form>

            <div class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Raw Email</h2>
                <pre class="mt-4 max-h-[34rem] overflow-auto rounded-lg bg-slate-950 p-4 text-sm leading-6 text-slate-100">{{ $email->body_text }}</pre>
            </div>
        </section>
    </div>
</x-layouts.app>
