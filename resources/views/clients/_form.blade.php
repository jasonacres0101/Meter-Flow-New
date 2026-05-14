@csrf
<div class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
    <section class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Client Details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="app-field md:col-span-2">Client name
                <input name="name" value="{{ old('name', $client->name ?? '') }}" class="app-field-control" required>
            </label>
            <label class="app-field">Account reference
                <input name="account_reference" value="{{ old('account_reference', $client->account_reference ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Contact email
                <input name="contact_email" type="email" value="{{ old('contact_email', $client->contact_email ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Phone
                <input name="phone" value="{{ old('phone', $client->phone ?? '') }}" class="app-field-control">
            </label>
            <label class="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active ?? true))>
                Active client
            </label>
            <label class="app-field md:col-span-2">Notes
                <textarea name="notes" class="app-field-control h-32">{{ old('notes', $client->notes ?? '') }}</textarea>
            </label>
        </div>
    </section>

    <aside class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Setup Flow</h2>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
            <div class="rounded-lg bg-teal-50 p-3 text-teal-900"><strong>1. Client</strong><span class="mt-1 block">Create the customer account inside your company workspace.</span></div>
            <div class="rounded-lg bg-slate-50 p-3"><strong>2. Sites</strong><span class="mt-1 block">Add one or more locations for this customer.</span></div>
            <div class="rounded-lg bg-slate-50 p-3"><strong>3. Machines</strong><span class="mt-1 block">Add machines by serial number so incoming emails can match correctly.</span></div>
        </div>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif
        <div class="mt-5 grid gap-2">
            <button class="app-button w-full" name="after_save" value="stay">{{ isset($client->id) ? 'Save client' : 'Create client' }}</button>
            @unless(isset($client->id))
                <button class="app-button-secondary w-full" name="after_save" value="add_site">Save and add site</button>
            @endunless
        </div>
    </aside>
</div>
