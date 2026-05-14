@csrf
@php
    $configurationJson = old('parser_configuration', isset($reportTemplate) ? json_encode($reportTemplate->parser_configuration ?? [], JSON_PRETTY_PRINT) : '{}');
    $configuration = json_decode($configurationJson ?: '{}', true) ?: [];
    $detectedLabels = collect($detectedFields ?? [])->pluck('label')->filter()->unique()->values();
    $detectedValueMap = collect($detectedFields ?? [])
        ->filter(fn ($field) => filled($field['label'] ?? null))
        ->mapWithKeys(fn ($field) => [$field['label'] => $field['value'] ?? ''])
        ->all();
    $mappingGroups = [
        'required' => [
            'title' => 'Required Fields',
            'hint' => 'These fields let the app identify the machine and create the main reading.',
            'fields' => [
                'serial_number_labels' => 'Serial number',
                'report_date_labels' => 'Report date/time',
                'total_counter_labels' => 'Total counter',
            ],
        ],
        'counters' => [
            'title' => 'Page Counters',
            'hint' => 'Map the counter labels that appear in this report. Leave missing counters blank.',
            'fields' => [
                'mono_counter_labels' => 'Mono/B&W total',
                'colour_counter_labels' => 'Colour total',
                'copy_mono_counter_labels' => 'Copy mono',
                'copy_colour_counter_labels' => 'Copy colour',
                'print_mono_counter_labels' => 'Print mono',
                'print_colour_counter_labels' => 'Print colour',
                'scan_counter_labels' => 'Scan count',
                'fax_sent_counter_labels' => 'Fax sent',
                'fax_received_counter_labels' => 'Fax received',
            ],
        ],
        'toner' => [
            'title' => 'Toner & Status',
            'hint' => 'Map toner levels and status fields if the email includes them.',
            'fields' => [
                'black_toner_percentage_labels' => 'Black toner',
                'cyan_toner_percentage_labels' => 'Cyan toner',
                'magenta_toner_percentage_labels' => 'Magenta toner',
                'yellow_toner_percentage_labels' => 'Yellow toner',
                'black_inserted_toner_number_labels' => 'Black inserted toner number',
                'cyan_inserted_toner_number_labels' => 'Cyan inserted toner number',
                'magenta_inserted_toner_number_labels' => 'Magenta inserted toner number',
                'yellow_inserted_toner_number_labels' => 'Yellow inserted toner number',
                'waste_toner_status_labels' => 'Waste toner status',
                'current_status_labels' => 'Current status',
                'service_status_labels' => 'Service status',
            ],
        ],
        'machine' => [
            'title' => 'Machine Info',
            'hint' => 'Optional labels for display information found in the report.',
            'fields' => [
                'machine_name_labels' => 'Machine name',
                'model_name_labels' => 'Model name',
            ],
        ],
    ];
    $wizardSteps = [
        'setup' => 'Setup',
        'required' => 'Required',
        'counters' => 'Counters',
        'toner' => 'Toner',
        'machine' => 'Machine',
        'review' => 'Review',
    ];
@endphp

@if(isset($sourceEmail) && $sourceEmail)
    <input type="hidden" name="incoming_report_email_id" value="{{ $sourceEmail->id }}">
@endif
<input type="hidden" name="sample_subject" value="{{ old('sample_subject', $reportTemplate->sample_subject ?? '') }}">

<div class="space-y-5" data-template-wizard>
    <div class="app-panel rounded-xl p-4">
        <div class="flex flex-wrap gap-2">
            @foreach($wizardSteps as $stepKey => $stepLabel)
                <button type="button" data-wizard-nav="{{ $stepKey }}" class="rounded-lg px-3 py-2 text-sm font-black {{ $loop->first ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $loop->iteration }}. {{ $stepLabel }}</button>
            @endforeach
        </div>
    </div>

    <section class="app-panel rounded-xl p-5" data-wizard-step="setup">
        <h2 class="text-lg font-black text-slate-950">Template Setup</h2>
        <p class="mt-1 text-sm text-slate-500">Confirm the model and parser. The email subject is saved automatically as a sample reference.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @if(isset($sourceEmail) && $sourceEmail && $sourceEmail->machine?->machineModel)
                <input type="hidden" name="machine_model_id" value="{{ old('machine_model_id', $sourceEmail->machine->machine_model_id) }}">
                <div class="rounded-lg bg-slate-50 p-3 text-sm md:col-span-2">
                    <span class="font-bold text-slate-500">Machine model</span>
                    <span class="mt-1 block font-semibold text-slate-950">{{ $sourceEmail->machine->machineModel->manufacturer }} {{ $sourceEmail->machine->machineModel->model_name }}</span>
                </div>
            @else
                <label class="app-field md:col-span-2">Machine model<select name="machine_model_id" class="app-field-control">@foreach($machineModels as $model)<option value="{{ $model->id }}" @selected(old('machine_model_id', $reportTemplate->machine_model_id ?? '') == $model->id)>{{ $model->manufacturer }} {{ $model->model_name }}</option>@endforeach</select></label>
            @endif
            <div class="rounded-lg bg-slate-50 p-3 text-sm">
                <span class="font-bold text-slate-500">Template name</span>
                <span class="mt-1 block font-semibold text-slate-950">{{ old('template_name', $reportTemplate->template_name ?? '') ?: 'Set automatically from manufacturer and model' }}</span>
                <span class="mt-1 block text-xs font-semibold text-slate-500">Names are controlled by the app so versions stay consistent across tenants.</span>
            </div>
            <label class="app-field">Parser type<select name="parser_type" class="app-field-control">@foreach($parserTypes as $parser => $label)<option value="{{ $parser }}" @selected(old('parser_type', $reportTemplate->parser_type ?? '') === $parser)>{{ $label }}</option>@endforeach</select></label>
            @if(isset($sourceEmail) && $sourceEmail)
                <div class="rounded-lg bg-slate-50 p-3 text-sm"><span class="font-bold text-slate-500">Matched machine</span><span class="mt-1 block font-semibold text-slate-950">{{ $sourceEmail->machine?->manufacturer }} {{ $sourceEmail->machine?->model }} / {{ $sourceEmail->machine?->serial_number }}</span></div>
            @endif
            <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-900"><span class="font-bold">Sample subject</span><span class="mt-1 block">{{ old('sample_subject', $reportTemplate->sample_subject ?? '') ?: 'Not supplied' }}</span></div>
        </div>
    </section>

    @foreach($mappingGroups as $stepKey => $group)
        <section class="hidden" data-wizard-step="{{ $stepKey }}">
            <div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
                <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
                    <section class="app-panel rounded-xl p-4">
                        <h2 class="text-base font-black text-slate-950">Raw Email</h2>
                        <textarea name="{{ $loop->first ? 'sample_body' : '' }}" data-sample-body-copy class="mt-3 h-[34rem] w-full rounded-lg border-slate-300 bg-slate-950 p-3 font-mono text-xs leading-5 text-slate-100">{{ old('sample_body', $reportTemplate->sample_body ?? '') }}</textarea>
                    </section>

                    <section class="app-panel rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-black text-slate-950">Detected Labels</h2>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $detectedLabels->count() }}</span>
                        </div>
                        <div class="mt-3 grid max-h-56 gap-2 overflow-auto pr-1 md:grid-cols-2 xl:grid-cols-1">
                            @forelse($detectedFields ?? [] as $field)
                                <button type="button" data-copy-label="{{ $field['label'] }}" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-left hover:border-teal-300 hover:bg-teal-50">
                                    <span class="block text-sm font-black text-slate-900">{{ $field['label'] }}</span>
                                    <span class="mt-1 block truncate font-mono text-xs text-slate-500">{{ $field['value'] }}</span>
                                </button>
                            @empty
                                <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No labels were detected. Use custom labels in the mapping panel.</p>
                            @endforelse
                        </div>
                    </section>
                </aside>

                <section class="app-panel rounded-xl p-5">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">{{ $group['title'] }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $group['hint'] }}</p>
                        </div>
                        <button type="button" data-toggle-json class="app-button-secondary">Advanced JSON</button>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($group['fields'] as $key => $label)
                            @php
                                $values = collect($configuration[$key] ?? [])->filter()->values();
                                $selected = $values->first(fn ($value) => $detectedLabels->contains($value));
                                $custom = $values->reject(fn ($value) => $detectedLabels->contains($value))->join(', ');
                            @endphp
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <div class="grid gap-3 lg:grid-cols-[0.45fr_1fr] lg:items-center">
                                    <div>
                                        <div class="font-black text-slate-950">{{ $label }}</div>
                                        <div class="mt-1 text-xs font-semibold text-slate-500" data-map-status="{{ $key }}">Not mapped</div>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-[1fr_0.85fr]">
                                        <select data-parser-map="{{ $key }}" class="app-field-control">
                                            <option value="">Choose label</option>
                                            @foreach($detectedLabels as $detectedLabel)
                                                <option value="{{ $detectedLabel }}" @selected($selected === $detectedLabel)>{{ $detectedLabel }}</option>
                                            @endforeach
                                        </select>
                                        <input data-parser-custom="{{ $key }}" value="{{ $custom }}" placeholder="Or type label" class="app-field-control">
                                    </div>
                                    <div class="hidden rounded-lg border border-teal-100 bg-teal-50 px-3 py-2 text-xs text-teal-900 lg:col-start-2" data-map-preview="{{ $key }}">
                                        <span class="font-black">Preview value</span>
                                        <span class="mt-1 block font-mono text-slate-800" data-map-preview-value="{{ $key }}"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <label class="app-field mt-4 hidden" data-json-panel>Parser configuration JSON
                        <textarea name="parser_configuration" data-parser-json class="app-field-control h-40 font-mono text-xs">{{ $configurationJson }}</textarea>
                    </label>
                </section>
            </div>
        </section>
    @endforeach

    <section class="hidden" data-wizard-step="review">
        <div class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Review & Save</h2>
            <p class="mt-1 text-sm text-slate-500">Check the generated mapping, then save this template.</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4 text-sm">
                    <div class="font-black text-slate-950">Template</div>
                    <div class="mt-2 text-slate-600">The template will be linked to the selected machine model and used when future reports from this model arrive.</div>
                </div>
                <label class="flex items-center gap-2 rounded-lg bg-slate-50 p-4 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $reportTemplate->is_active ?? true))> Active template</label>
            </div>

            <section class="mt-5 rounded-xl border border-slate-200 bg-white p-4" data-review-example>
                <div class="flex flex-col gap-1 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Example Parsed Reading</h3>
                        <p class="text-sm text-slate-500">Built from the sample email and the fields mapped in this wizard.</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">Live preview</span>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase text-slate-500">Machine</div>
                        <div class="mt-1 truncate text-lg font-black text-slate-950" data-review-machine-name>Not mapped</div>
                        <div class="mt-1 text-xs font-semibold text-slate-500" data-review-machine-meta>Serial not mapped</div>
                    </div>
                    <div class="rounded-lg bg-slate-950 p-3 text-white">
                        <div class="text-xs font-bold uppercase text-slate-300">Total counter</div>
                        <div class="mt-1 text-2xl font-black" data-review-total-counter>-</div>
                        <div class="mt-1 text-xs font-semibold text-slate-300" data-review-reading-date>Date not mapped</div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-950">
                        <div class="text-xs font-bold uppercase text-emerald-700">Status</div>
                        <div class="mt-1 text-lg font-black" data-review-status>Not mapped</div>
                        <div class="mt-1 text-xs font-semibold text-emerald-700" data-review-service-status>Service status not mapped</div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_1fr]">
                    <div>
                        <h4 class="text-sm font-black text-slate-950">Page counters</h4>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2" data-review-counters></div>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-950">Toner levels</h4>
                        <div class="mt-2 space-y-2" data-review-toners></div>
                    </div>
                </div>
            </section>

            <label class="app-field mt-4">Generated parser configuration
                <textarea data-review-json class="app-field-control h-56 font-mono text-xs" readonly></textarea>
            </label>
        </div>
    </section>

    @if ($errors->any())<div class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

    <div class="app-panel flex items-center justify-between rounded-xl p-4">
        <button type="button" data-wizard-prev class="app-button-secondary">Back</button>
        <div class="flex gap-2">
            <button type="button" data-wizard-next class="app-button">Next</button>
            <button data-wizard-save class="app-button hidden">Save template</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const json = document.querySelector('[data-parser-json]');
        const reviewJson = document.querySelector('[data-review-json]');
        const jsonPanels = [...document.querySelectorAll('[data-json-panel]')];
        const toggleButtons = [...document.querySelectorAll('[data-toggle-json]')];
        const controls = [...document.querySelectorAll('[data-parser-map], [data-parser-custom]')];
        const steps = [...document.querySelectorAll('[data-wizard-step]')];
        const navButtons = [...document.querySelectorAll('[data-wizard-nav]')];
        const prev = document.querySelector('[data-wizard-prev]');
        const next = document.querySelector('[data-wizard-next]');
        const save = document.querySelector('[data-wizard-save]');
        const reviewMachineName = document.querySelector('[data-review-machine-name]');
        const reviewMachineMeta = document.querySelector('[data-review-machine-meta]');
        const reviewTotalCounter = document.querySelector('[data-review-total-counter]');
        const reviewReadingDate = document.querySelector('[data-review-reading-date]');
        const reviewStatus = document.querySelector('[data-review-status]');
        const reviewServiceStatus = document.querySelector('[data-review-service-status]');
        const reviewCounters = document.querySelector('[data-review-counters]');
        const reviewToners = document.querySelector('[data-review-toners]');
        const detectedValues = {{ Illuminate\Support\Js::from($detectedValueMap) }};
        const stepOrder = ['setup', 'required', 'counters', 'toner', 'machine', 'review'];
        const previewCleanupRules = {
            black_toner_percentage_labels: 'before_pipe',
            cyan_toner_percentage_labels: 'before_pipe',
            magenta_toner_percentage_labels: 'before_pipe',
            yellow_toner_percentage_labels: 'before_pipe',
        };
        let activeStep = 'setup';
        let activeCustomInput = null;

        if (! json) {
            return;
        }

        const syncSampleBodies = (source) => {
            document.querySelectorAll('[data-sample-body-copy]').forEach((textarea) => {
                if (textarea !== source) {
                    textarea.value = source.value;
                }
            });
        };

        const previewValueFor = (key, value) => {
            if (previewCleanupRules[key] === 'before_pipe') {
                return String(value).split('|')[0].trim();
            }

            return value;
        };

        const detectedKeyFor = (label) => Object.prototype.hasOwnProperty.call(detectedValues, label)
            ? label
            : Object.keys(detectedValues).find((key) => key.toLowerCase() === label.toLowerCase());

        const mappedValue = (config, key) => {
            const labels = config[key] || [];

            for (const label of labels) {
                const detectedKey = detectedKeyFor(label);

                if (detectedKey) {
                    return previewValueFor(key, detectedValues[detectedKey] || '');
                }
            }

            return null;
        };

        const numericPercent = (value) => {
            const match = String(value || '').match(/\d+/);

            return match ? Math.min(100, Math.max(0, Number(match[0]))) : null;
        };

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const syncReviewPreview = (config) => {
            if (! reviewCounters || ! reviewToners) {
                return;
            }

            const machineName = mappedValue(config, 'machine_name_labels') || 'Machine name not mapped';
            const modelName = mappedValue(config, 'model_name_labels');
            const serialNumber = mappedValue(config, 'serial_number_labels');
            const totalCounter = mappedValue(config, 'total_counter_labels');
            const readingDate = mappedValue(config, 'report_date_labels');
            const currentStatus = mappedValue(config, 'current_status_labels');
            const serviceStatus = mappedValue(config, 'service_status_labels');
            const counterRows = [
                ['Mono/B&W', mappedValue(config, 'mono_counter_labels')],
                ['Colour', mappedValue(config, 'colour_counter_labels')],
                ['Copy mono', mappedValue(config, 'copy_mono_counter_labels')],
                ['Copy colour', mappedValue(config, 'copy_colour_counter_labels')],
                ['Print mono', mappedValue(config, 'print_mono_counter_labels')],
                ['Print colour', mappedValue(config, 'print_colour_counter_labels')],
                ['Scan', mappedValue(config, 'scan_counter_labels')],
                ['Fax sent', mappedValue(config, 'fax_sent_counter_labels')],
                ['Fax received', mappedValue(config, 'fax_received_counter_labels')],
            ];
            const tonerRows = [
                ['Black', mappedValue(config, 'black_toner_percentage_labels'), mappedValue(config, 'black_inserted_toner_number_labels'), 'bg-slate-950'],
                ['Cyan', mappedValue(config, 'cyan_toner_percentage_labels'), mappedValue(config, 'cyan_inserted_toner_number_labels'), 'bg-cyan-500'],
                ['Magenta', mappedValue(config, 'magenta_toner_percentage_labels'), mappedValue(config, 'magenta_inserted_toner_number_labels'), 'bg-pink-600'],
                ['Yellow', mappedValue(config, 'yellow_toner_percentage_labels'), mappedValue(config, 'yellow_inserted_toner_number_labels'), 'bg-amber-400'],
            ];

            reviewMachineName.textContent = machineName;
            reviewMachineMeta.textContent = [modelName, serialNumber ? `Serial ${serialNumber}` : null].filter(Boolean).join(' / ') || 'Serial not mapped';
            reviewTotalCounter.textContent = totalCounter || '-';
            reviewReadingDate.textContent = readingDate || 'Date not mapped';
            reviewStatus.textContent = currentStatus || 'Not mapped';
            reviewServiceStatus.textContent = serviceStatus ? `Service: ${serviceStatus}` : 'Service status not mapped';
            reviewCounters.innerHTML = counterRows.map(([label, value]) => `
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <div class="text-xs font-bold text-slate-500">${escapeHtml(label)}</div>
                    <div class="mt-1 font-mono text-sm font-black text-slate-950">${escapeHtml(value || '-')}</div>
                </div>
            `).join('');
            reviewToners.innerHTML = tonerRows.map(([label, value, insertedNumber, colourClass]) => {
                const percent = numericPercent(value);

                return `
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-black text-slate-950">${escapeHtml(label)}</span>
                            <span class="font-mono font-black text-slate-700">${escapeHtml(value || '-')}</span>
                        </div>
                        <div class="mt-1 text-xs font-semibold text-slate-500">Inserted: ${escapeHtml(insertedNumber || '-')}</div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full ${colourClass}" style="width: ${percent ?? 0}%"></div>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const syncJson = () => {
            const config = {};

            document.querySelectorAll('[data-parser-map]').forEach((select) => {
                const key = select.dataset.parserMap;
                const custom = document.querySelector(`[data-parser-custom="${key}"]`);
                const status = document.querySelector(`[data-map-status="${key}"]`);
                const preview = document.querySelector(`[data-map-preview="${key}"]`);
                const previewValue = document.querySelector(`[data-map-preview-value="${key}"]`);
                const labels = [];

                if (select.value) {
                    labels.push(select.value);
                }

                if (custom?.value) {
                    custom.value.split(',').map((value) => value.trim()).filter(Boolean).forEach((value) => labels.push(value));
                }

                const uniqueLabels = [...new Set(labels)];

                if (uniqueLabels.length) {
                    config[key] = uniqueLabels;
                }

                if (status) {
                    status.textContent = uniqueLabels.length ? `Mapped to ${uniqueLabels.join(', ')}` : 'Not mapped';
                    status.className = `mt-1 text-xs font-semibold ${uniqueLabels.length ? 'text-teal-700' : 'text-slate-500'}`;
                }

                if (preview && previewValue) {
                    const previewRows = uniqueLabels.map((label) => {
                        const detectedKey = detectedKeyFor(label);

                        if (! detectedKey) {
                            return null;
                        }

                        const value = previewValueFor(key, detectedValues[detectedKey] || 'Blank value');

                        return `${detectedKey}: ${value}`;
                    }).filter(Boolean);

                    preview.classList.toggle('hidden', previewRows.length === 0);
                    previewValue.textContent = previewRows.join(' | ');
                }
            });

            json.value = JSON.stringify(config, null, 2);

            if (reviewJson) {
                reviewJson.value = json.value;
            }

            syncReviewPreview(config);
        };

        const showStep = (step) => {
            activeStep = step;
            steps.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.wizardStep !== step));
            navButtons.forEach((button) => {
                const active = button.dataset.wizardNav === step;
                button.className = `rounded-lg px-3 py-2 text-sm font-black ${active ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700'}`;
            });

            const index = stepOrder.indexOf(step);
            prev.classList.toggle('invisible', index === 0);
            next.classList.toggle('hidden', step === 'review');
            save.classList.toggle('hidden', step !== 'review');
            syncJson();
        };

        controls.forEach((control) => {
            control.addEventListener('focus', () => {
                if (control.matches('[data-parser-custom]')) {
                    activeCustomInput = control;
                }
            });
            control.addEventListener('input', syncJson);
            control.addEventListener('change', syncJson);
        });

        document.querySelectorAll('[data-sample-body-copy]').forEach((textarea) => {
            textarea.addEventListener('input', () => syncSampleBodies(textarea));
        });

        document.querySelectorAll('[data-copy-label]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = activeCustomInput || document.querySelector(`[data-wizard-step="${activeStep}"] [data-parser-custom]`) || document.querySelector('[data-parser-custom]');

                if (! target) {
                    return;
                }

                const existing = target.value.split(',').map((value) => value.trim()).filter(Boolean);
                existing.push(button.dataset.copyLabel);
                target.value = [...new Set(existing)].join(', ');
                target.focus();
                syncJson();
            });
        });

        navButtons.forEach((button) => button.addEventListener('click', () => showStep(button.dataset.wizardNav)));
        prev?.addEventListener('click', () => showStep(stepOrder[Math.max(0, stepOrder.indexOf(activeStep) - 1)]));
        next?.addEventListener('click', () => showStep(stepOrder[Math.min(stepOrder.length - 1, stepOrder.indexOf(activeStep) + 1)]));
        toggleButtons.forEach((button) => button.addEventListener('click', () => jsonPanels.forEach((panel) => panel.classList.toggle('hidden'))));

        syncJson();
        showStep(activeStep);
    });
</script>
