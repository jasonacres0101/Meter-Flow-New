<x-layouts.app :title="$email->subject ?: 'Parser Review'">
    @php
        $configurationJson = json_encode($suggestedConfiguration, JSON_PRETTY_PRINT);
        $canApprove = filled($email->machine_id);
        $hasAiSuggestion = filled($aiSuggestion);
        $aiConfidenceScore = (int) ($aiSuggestion['confidence_score'] ?? 0);
        $aiConfidenceTone = $aiConfidenceScore >= 75 ? 'bg-emerald-50 text-emerald-700' : ($aiConfidenceScore >= 45 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700');
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Parser review</div>
            <h1 class="app-page-title mt-2">{{ $email->subject ?: 'Incoming report' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $email->company?->name ?? 'Unknown account' }} / {{ $email->from_email ?: 'Unknown sender' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="{{ route('parser-queue.ai-suggestion', $email) }}">
                @csrf
                <button class="app-button">Ask AI for mapping</button>
            </form>
            <a href="{{ route('parser-queue.index') }}" class="app-button-secondary">Back to queue</a>
        </div>
    </div>

    @if($errors->has('ai'))
        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first('ai') }}</div>
    @endif

    <div class="mb-5 rounded-lg border border-slate-200 bg-white p-4 text-sm shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="font-black text-slate-950">AI guidance</div>
                <p class="mt-1 leading-6 text-slate-600">{{ $aiReviewRecommendation['reason'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $aiReviewRecommendation['tone'] }}">{{ $aiReviewRecommendation['label'] }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $aiReviewRecommendation['confidence_label'] }} local confidence · {{ $aiReviewRecommendation['confidence_score'] }}%</span>
            </div>
        </div>
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
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $hasAiSuggestion ? 'bg-purple-50 text-purple-700' : 'bg-teal-50 text-teal-700' }}">{{ $hasAiSuggestion ? 'AI draft' : 'Draft' }}</span>
                </div>

                @if($hasAiSuggestion && filled($aiSuggestion['explanation'] ?? null))
                    <div class="mt-4 rounded-lg bg-purple-50 p-3 text-sm text-purple-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ $aiSuggestion['explanation'] }}</span>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $aiConfidenceTone }}">AI confidence {{ $aiConfidenceScore }}%</span>
                        </div>
                    </div>
                @endif

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

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-black text-slate-950">Mapping Review</h3>
                            <p class="mt-1 text-sm text-slate-600">Check these fields before approving. Any red row should be fixed in the JSON mapping first.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs font-black">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">{{ $mappingReview['mapped_count'] }} matched</span>
                            <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">{{ $mappingReview['missing_count'] }} needs review</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ $mappingReview['unused_count'] }} unused detected</span>
                        </div>
                    </div>

                    <div class="mt-4 max-h-72 overflow-auto rounded-lg border border-slate-200 bg-white">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Parser field</th>
                                    <th class="px-3 py-2">Mapped label</th>
                                    <th class="px-3 py-2">Value</th>
                                    <th class="px-3 py-2">Review</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($mappingReview['rows'] as $row)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $row['key'] }}</td>
                                        <td class="px-3 py-2 font-semibold text-slate-950">{{ $row['label'] ?: 'Not mapped' }}</td>
                                        <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ filled($row['value']) ? $row['value'] : '—' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $row['tone'] }}">{{ $row['status'] }}</span>
                                            <span class="ml-2 text-xs text-slate-500">{{ $row['note'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($mappingReview['unused_count'])
                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm font-bold text-slate-700">Show unused detected fields</summary>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach($mappingReview['unused_fields'] as $field)
                                    <div class="rounded-lg bg-white p-3 text-sm">
                                        <div class="font-black text-slate-950">{{ $field['label'] }}</div>
                                        <div class="mt-1 truncate font-mono text-xs text-slate-500">{{ $field['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>

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
