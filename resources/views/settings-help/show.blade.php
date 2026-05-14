<x-layouts.app title="Setup Help">
    <div class="mb-8 overflow-hidden rounded-xl bg-slate-950 text-white shadow-2xl shadow-slate-900/15">
        <div class="grid gap-6 p-6 lg:grid-cols-[1.25fr_0.75fr] lg:p-8">
            <div>
                <div class="text-sm font-bold uppercase tracking-wide text-teal-300">Settings guide</div>
                <h1 class="mt-3 text-3xl font-black tracking-tight">Set up copier emails and reporting with confidence.</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">Use this guide when onboarding a new company, connecting report mailboxes, adding machines, and keeping revenue and toner alerts accurate.</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-5">
                <div class="text-sm font-semibold text-slate-300">Recommended order</div>
                <ol class="mt-3 space-y-2 text-sm font-semibold">
                    <li>1. Add clients, sites and machines</li>
                    <li>2. Add or select machine models</li>
                    <li>3. Configure email sources</li>
                    <li>4. Let support handle new report formats</li>
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
                    <a class="rounded-lg bg-amber-50 px-3 py-2 text-amber-800" href="{{ route('email-sources.index') }}">Email sources</a>
                    <a class="rounded-lg bg-rose-50 px-3 py-2 text-rose-800" href="{{ route('incoming-report-emails.index') }}">Report emails</a>
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
                <h2 class="text-xl font-black">2. Choose or Add Machine Models</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600">Select an existing model when adding machines. If the model is not listed, add the manufacturer and model name only; support handles report format setup behind the scenes.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-4"><div class="font-black">Manufacturer</div><p class="mt-2 text-sm text-slate-600">Choose the make, such as Sharp, Canon, Ricoh or Konica Minolta.</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><div class="font-black">Model</div><p class="mt-2 text-sm text-slate-600">Enter the exact model name shown on the device or report email.</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><div class="font-black">Report setup</div><p class="mt-2 text-sm text-slate-600">If a new email format arrives, it will show as setup in progress while support approves it.</p></div>
                </div>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">3. Connect Email Sources</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Gmail</div><p class="mt-2 text-sm text-slate-600">Use an app password or approved mailbox credentials. Poll the mailbox folder that receives copier reports.</p></div>
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Office 365</div><p class="mt-2 text-sm text-slate-600">Use Microsoft Graph modern authentication with tenant ID, client ID, client secret and scope.</p></div>
                    <div class="rounded-lg border border-slate-200 p-4"><div class="font-black">Custom POP/IMAP</div><p class="mt-2 text-sm text-slate-600">Use host, port, encryption, username, password and folder settings for standard mailboxes.</p></div>
                </div>
                <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-900">
                    <strong>Mailbox behaviour:</strong> keep report messages available until reporting is active and confirmed.
                </div>
            </section>

            <section class="app-panel rounded-xl p-6">
                <h2 class="text-xl font-black">4. Report Email Statuses</h2>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-lg bg-slate-50 p-4 text-sm"><strong>Processing:</strong> stored and waiting to be handled.</div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900"><strong>Reporting active:</strong> matched to a machine and creating readings.</div>
                    <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900"><strong>Setup in progress:</strong> support is approving this report format.</div>
                    <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-900"><strong>Waiting for machine match:</strong> check the serial number on the machine record.</div>
                    <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-900"><strong>Support review needed:</strong> support needs to review the report.</div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">New report formats are handled by support. Once setup is complete, waiting emails are reprocessed automatically.</p>
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
