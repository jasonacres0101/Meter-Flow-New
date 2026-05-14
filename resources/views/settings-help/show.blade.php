<x-layouts.app title="Setup Help">
    <div class="mb-8 overflow-hidden rounded-xl bg-slate-950 text-white shadow-2xl shadow-slate-900/15">
        <div class="grid gap-6 p-6 lg:grid-cols-[1.25fr_0.75fr] lg:p-8">
            <div>
                <div class="text-sm font-bold uppercase tracking-wide text-teal-300">Settings guide</div>
                <h1 class="mt-3 text-3xl font-black tracking-tight">Set up copier emails, templates and reporting with confidence.</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">Use this guide when onboarding a new company, connecting report mailboxes, adding copier models, testing parsers, and keeping revenue and toner alerts accurate.</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-5">
                <div class="text-sm font-semibold text-slate-300">Recommended order</div>
                <ol class="mt-3 space-y-2 text-sm font-semibold">
                    <li>1. Add clients, sites and machines</li>
                    <li>2. Add machine models and templates</li>
                    <li>3. Configure email sources</li>
                    <li>4. Review unmatched emails</li>
                    <li>5. Set pricing and toner alerts</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.7fr_1.3fr]">
        <aside class="space-y-4">
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Quick Links</h2>
                <div class="mt-4 grid gap-2 text-sm font-bold">
                    <a class="rounded-lg bg-teal-50 px-3 py-2 text-teal-800" href="{{ route('machines.create') }}">Add a machine</a>
                    <a class="rounded-lg bg-blue-50 px-3 py-2 text-blue-800" href="{{ route('machine-models.index') }}">Machine models</a>
                    <a class="rounded-lg bg-slate-100 px-3 py-2 text-slate-800" href="{{ route('report-templates.index') }}">Report templates</a>
                    <a class="rounded-lg bg-amber-50 px-3 py-2 text-amber-800" href="{{ route('email-sources.index') }}">Email sources</a>
                    <a class="rounded-lg bg-rose-50 px-3 py-2 text-rose-800" href="{{ route('incoming-report-emails.index') }}">Incoming email store</a>
                </div>
            </section>

            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black">Matching Rule</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Machines are matched primarily by serial number. Always enter the exact serial number from the copier before relying on automatic email processing.</p>
            </section>
        </aside>

        <div class="space-y-6">
            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">1. Add Clients, Sites and Machines</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-4"><div class="font-black">Client</div><p class="mt-2 text-sm text-slate-600">Represents the customer account. Pricing defaults are set per client.</p></div>
                    <div class="rounded-lg bg-blue-50 p-4"><div class="font-black text-blue-950">Site</div><p class="mt-2 text-sm text-blue-900">Represents a customer location. Site pricing can override client pricing.</p></div>
                    <div class="rounded-lg bg-teal-50 p-4"><div class="font-black text-teal-950">Machine</div><p class="mt-2 text-sm text-teal-900">Represents the copier or printer. Serial number is the main email matching key.</p></div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">For each machine, enter manufacturer, model, serial number, machine name, location, IP address, expected sender email and active status. Do not let incoming reports create machines automatically.</p>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">2. Configure Machine Models and Templates</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="app-table">
                        <tbody>
                        <tr><td class="font-bold">Machine model</td><td>Manufacturer, model name, parser type and notes. Example: Sharp / MX-2630N / sharp_mx_status_email.</td></tr>
                        <tr><td class="font-bold">Report template</td><td>Stores a sample subject, sample body, parser type and JSON parser configuration for that model.</td></tr>
                        <tr><td class="font-bold">Parser type</td><td>Must match a parser registered in the ParserFactory, such as sharp_mx_status_email or generic_counter_email.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 rounded-lg bg-amber-50 p-4 text-sm font-semibold text-amber-900">Tip: paste a real device email into the template sample body. It gives future parser changes a reliable example format.</p>

                <div class="mt-5 rounded-lg border border-slate-200 p-4">
                    <h3 class="font-black text-slate-950">Parser field mapping reference</h3>
                    <p class="mt-1 text-sm text-slate-600">These are the common fields the parser can pull. The right-hand value is the JSON/config key used when mapping email labels.</p>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4">
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

                        <div class="rounded-lg bg-slate-50 p-4">
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

                        <div class="rounded-lg bg-slate-50 p-4">
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

                        <div class="rounded-lg bg-slate-50 p-4">
                            <div class="font-bold text-slate-900">Paper and maintenance</div>
                            <dl class="mt-2 space-y-1 text-xs text-slate-600">
                                <div class="flex justify-between gap-3"><dt>Paper tray status</dt><dd class="font-mono text-slate-900">paper_tray_status</dd></div>
                                <div class="flex justify-between gap-3"><dt>Tray 1 status</dt><dd class="font-mono text-slate-900">tray_1_status</dd></div>
                                <div class="flex justify-between gap-3"><dt>Tray 2 status</dt><dd class="font-mono text-slate-900">tray_2_status</dd></div>
                                <div class="flex justify-between gap-3"><dt>Bypass tray status</dt><dd class="font-mono text-slate-900">bypass_tray_status</dd></div>
                                <div class="flex justify-between gap-3"><dt>Maintenance counter status</dt><dd class="font-mono text-slate-900">maintenance_counter_status</dd></div>
                                <div class="flex justify-between gap-3"><dt>Service status</dt><dd class="font-mono text-slate-900">service_status</dd></div>
                            </dl>
                        </div>
                    </div>

                    <pre class="mt-4 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-white">{
  "machine_name_labels": ["Machine Name", "Device Name"],
  "serial_number_labels": ["Serial Number", "Serial No"],
  "total_counter_labels": ["Total Counter", "Total Pages"],
  "black_toner_percentage_labels": ["Black Toner", "K Toner"]
}</pre>
                </div>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">3. Connect Email Sources</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Gmail</div><p class="mt-2 text-sm text-slate-600">Use an app password or approved mailbox credentials. Poll the mailbox folder that receives copier reports.</p></div>
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Office 365</div><p class="mt-2 text-sm text-slate-600">Use Microsoft Graph modern authentication with tenant ID, client ID, client secret and scope.</p></div>
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Custom IMAP</div><p class="mt-2 text-sm text-slate-600">Use host, port, encryption, username, password and folder settings for standard IMAP mailboxes.</p></div>
                </div>
                <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-900">
                    <strong>Mailbox behaviour:</strong> choose whether processed messages are marked as seen or left unread. Avoid deleting messages until the parser and matching flow has been proven.
                </div>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">4. Process and Review Incoming Emails</h2>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-lg bg-slate-50 p-4 text-sm"><strong>Pending:</strong> stored but not parsed yet.</div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900"><strong>Parsed:</strong> matched to a machine and created meter or consumable readings.</div>
                    <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900"><strong>Unmatched:</strong> no machine serial number match was found. Check the serial number on the machine record.</div>
                    <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-900"><strong>Failed:</strong> parser could not read the email. Check the parser type and template sample.</div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Raw emails are stored permanently, including subject, body, raw payload and parse errors. This allows old reports to be reprocessed when parsers improve.</p>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">5. Pricing, Toner Alerts and Reports</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-teal-50 p-4"><div class="font-black text-teal-950">Pricing</div><p class="mt-2 text-sm text-teal-900">Set B/W and colour pence-per-page at client level, then override for sites or individual machines.</p></div>
                    <div class="rounded-lg bg-rose-50 p-4"><div class="font-black text-rose-950">Toner alerts</div><p class="mt-2 text-sm text-rose-900">Set warning and critical thresholds for black, cyan, magenta and yellow consumables.</p></div>
                    <div class="rounded-lg bg-blue-50 p-4"><div class="font-black text-blue-950">Reports</div><p class="mt-2 text-sm text-blue-900">Generate client, site or machine reports with custom, monthly, quarterly or yearly periods.</p></div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Daily usage is calculated from differences between consecutive readings. If a previous reading is missing, usage is unknown. If a counter decreases, the reading is flagged as a possible reset.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
