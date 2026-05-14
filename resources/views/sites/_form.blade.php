@csrf
<div class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
    <section class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Site Details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="app-field md:col-span-2">Client
                <select name="client_id" class="app-field-control" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $site->client_id ?? request('client_id')) == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="app-field md:col-span-2">Site name
                <input name="name" value="{{ old('name', $site->name ?? '') }}" class="app-field-control" required>
            </label>
            <label class="app-field md:col-span-2">Address line 1
                <input name="address_line_1" value="{{ old('address_line_1', $site->address_line_1 ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field md:col-span-2">Address line 2
                <input name="address_line_2" value="{{ old('address_line_2', $site->address_line_2 ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Town / city
                <input name="city" value="{{ old('city', $site->city ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Postcode
                <input name="postcode" value="{{ old('postcode', $site->postcode ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Latitude
                <input name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $site->latitude ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Longitude
                <input name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $site->longitude ?? '') }}" class="app-field-control">
            </label>
            <label class="app-field">Contact email
                <input name="contact_email" type="email" value="{{ old('contact_email', $site->contact_email ?? '') }}" class="app-field-control">
            </label>
            <label class="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $site->is_active ?? true))>
                Active site
            </label>
            <label class="app-field md:col-span-2">Notes
                <textarea name="notes" class="app-field-control h-32">{{ old('notes', $site->notes ?? '') }}</textarea>
            </label>
        </div>
    </section>

    <aside class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Location Setup</h2>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
            <div class="rounded-lg bg-teal-50 p-3 text-teal-900"><strong>Site mapping</strong><span class="mt-1 block">Latitude and longitude are optional, but they allow this location to appear on the site map.</span></div>
            <div class="rounded-lg bg-slate-50 p-3"><strong>Next step</strong><span class="mt-1 block">After saving, add machines to this site using their exact serial numbers.</span></div>
        </div>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif
        <div class="mt-5 grid gap-2">
            <button class="app-button w-full" name="after_save" value="stay">{{ isset($site->id) ? 'Save site' : 'Create site' }}</button>
            @unless(isset($site->id))
                <button class="app-button-secondary w-full" name="after_save" value="add_machine">Save and add machine</button>
            @endunless
        </div>
    </aside>
</div>
