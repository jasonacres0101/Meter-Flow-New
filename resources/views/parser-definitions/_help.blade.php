<aside class="app-panel rounded-xl p-5">
    <h2 class="text-base font-black text-slate-950">Parser Profile Help</h2>
    <p class="mt-2 text-sm text-slate-600">Use parser profiles to add SaaS-admin parser options without creating PHP files from the browser.</p>

    <div class="mt-5 space-y-4 text-sm text-slate-700">
        <div>
            <div class="font-bold text-slate-950">1. Choose a clear name</div>
            <p class="mt-1">Use the make, model family or email format.</p>
            <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">Ricoh status email</div>
        </div>

        <div>
            <div class="font-bold text-slate-950">2. Add a parser key</div>
            <p class="mt-1">Use lowercase words separated by underscores. This is what machine models store.</p>
            <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">ricoh_status_email</div>
        </div>

        <div>
            <div class="font-bold text-slate-950">3. Pick the closest built-in engine</div>
            <p class="mt-1"><strong>Sharp MX</strong> is for Sharp style status emails. <strong>Generic counter</strong> is best for simpler emails where counters can be mapped by labels.</p>
        </div>

        <div>
            <div class="font-bold text-slate-950">4. Add default JSON if needed</div>
            <p class="mt-1">Leave this as <span class="font-mono">{}</span> unless the email uses different labels or date formats.</p>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-white">{
  "serial_number_labels": ["Serial No", "Device Serial"],
  "total_counter_labels": ["Total Count", "Total Pages"],
  "date_format": "d/m/Y H:i:s",
  "timezone": "Europe/London"
}</pre>
        </div>

        <div>
            <div class="font-bold text-slate-950">5. Use it on a master model</div>
            <p class="mt-1">After saving, go to Master Models and select this parser profile for the make/model. Companies can then use that prebuilt model.</p>
        </div>

        <div class="border-t border-slate-200 pt-4">
            <div class="font-bold text-slate-950">Field mapping reference</div>
            <p class="mt-1">Use the JSON key when mapping labels in parser configuration. A parser may not find every field in every email. Missing fields are left blank.</p>

            <div class="mt-3 grid gap-3">
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-bold text-slate-900">Machine identity</div>
                    <dl class="mt-2 space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Machine name</dt><dd class="font-mono text-slate-900">machine_name</dd></div>
                        <div class="flex justify-between gap-3"><dt>Model name</dt><dd class="font-mono text-slate-900">model_name</dd></div>
                        <div class="flex justify-between gap-3"><dt>Serial number</dt><dd class="font-mono text-slate-900">serial_number</dd></div>
                        <div class="flex justify-between gap-3"><dt>Machine address / IP</dt><dd class="font-mono text-slate-900">machine_address</dd></div>
                        <div class="flex justify-between gap-3"><dt>Report date and time</dt><dd class="font-mono text-slate-900">reported_at</dd></div>
                        <div class="flex justify-between gap-3"><dt>Current status</dt><dd class="font-mono text-slate-900">current_status</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-bold text-slate-900">Meter counters</div>
                    <dl class="mt-2 space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Total counter</dt><dd class="font-mono text-slate-900">total_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Black and white count</dt><dd class="font-mono text-slate-900">mono_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Colour count</dt><dd class="font-mono text-slate-900">colour_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Copy black and white</dt><dd class="font-mono text-slate-900">copy_mono_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Copy colour</dt><dd class="font-mono text-slate-900">copy_colour_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Print black and white</dt><dd class="font-mono text-slate-900">print_mono_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Print colour</dt><dd class="font-mono text-slate-900">print_colour_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Scan count</dt><dd class="font-mono text-slate-900">scan_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Fax sent count</dt><dd class="font-mono text-slate-900">fax_sent_counter</dd></div>
                        <div class="flex justify-between gap-3"><dt>Fax received count</dt><dd class="font-mono text-slate-900">fax_received_counter</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-bold text-slate-900">Consumables</div>
                    <dl class="mt-2 space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Black toner percentage</dt><dd class="font-mono text-slate-900">black_toner_percentage</dd></div>
                        <div class="flex justify-between gap-3"><dt>Cyan toner percentage</dt><dd class="font-mono text-slate-900">cyan_toner_percentage</dd></div>
                        <div class="flex justify-between gap-3"><dt>Magenta toner percentage</dt><dd class="font-mono text-slate-900">magenta_toner_percentage</dd></div>
                        <div class="flex justify-between gap-3"><dt>Yellow toner percentage</dt><dd class="font-mono text-slate-900">yellow_toner_percentage</dd></div>
                        <div class="flex justify-between gap-3"><dt>Waste toner status</dt><dd class="font-mono text-slate-900">waste_toner_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Consumable status</dt><dd class="font-mono text-slate-900">consumable_status</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-bold text-slate-900">Paper and maintenance</div>
                    <dl class="mt-2 space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Paper tray status</dt><dd class="font-mono text-slate-900">paper_tray_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Tray 1 status</dt><dd class="font-mono text-slate-900">tray_1_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Tray 2 status</dt><dd class="font-mono text-slate-900">tray_2_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Bypass tray status</dt><dd class="font-mono text-slate-900">bypass_tray_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Maintenance counter status</dt><dd class="font-mono text-slate-900">maintenance_counter_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Service status</dt><dd class="font-mono text-slate-900">service_status</dd></div>
                        <div class="flex justify-between gap-3"><dt>Parse error</dt><dd class="font-mono text-slate-900">parse_error</dd></div>
                    </dl>
                </div>
            </div>

            <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-white">{
  "machine_name_labels": ["Machine Name", "Device Name"],
  "serial_number_labels": ["Serial Number", "Serial No"],
  "total_counter_labels": ["Total Counter", "Total Pages"],
  "black_toner_percentage_labels": ["Black Toner", "K Toner"]
}</pre>
        </div>
    </div>
</aside>
