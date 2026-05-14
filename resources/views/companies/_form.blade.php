@csrf
<div class="grid gap-4 md:grid-cols-2">
    <label class="app-field">Company name<input name="name" value="{{ old('name', $company->name ?? '') }}" class="app-field-control"></label>
    <label class="app-field">Account reference<input name="account_reference" value="{{ old('account_reference', $company->account_reference ?? '') }}" class="app-field-control"></label>
    <label class="app-field">Company number<input name="company_number" value="{{ old('company_number', $company->company_number ?? '') }}" class="app-field-control" placeholder="Registered company number"></label>
    <label class="app-field">VAT number<input name="vat_number" value="{{ old('vat_number', $company->vat_number ?? '') }}" class="app-field-control" placeholder="GB123456789"></label>
    <label class="app-field">Billing email<input name="billing_email" value="{{ old('billing_email', $company->billing_email ?? '') }}" class="app-field-control"></label>
    <label class="app-field">Monthly machine rate override<input name="monthly_machine_rate_override" type="number" min="0" step="0.01" value="{{ old('monthly_machine_rate_override', $company->monthly_machine_rate_override ?? '') }}" class="app-field-control" placeholder="Leave blank to use global SaaS rate"></label>
    <label class="app-field">Phone<input name="phone" value="{{ old('phone', $company->phone ?? '') }}" class="app-field-control"></label>
    <label class="app-field md:col-span-2">Website<input name="website" value="{{ old('website', $company->website ?? '') }}" class="app-field-control" placeholder="https://example.com"></label>
</div>

<div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
        <label class="app-field flex-1">Address lookup
            <input id="address-lookup" class="app-field-control" placeholder="Enter a UK postcode">
        </label>
        <button type="button" id="postcode-lookup-button" class="app-button-secondary">Lookup postcode</button>
    </div>
    <p id="postcode-lookup-status" class="mt-2 text-sm text-slate-500">Lookup fills town, county and country. Enter building and street manually.</p>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <label class="app-field">Address line 1<input name="address_line_1" value="{{ old('address_line_1', $company->address_line_1 ?? '') }}" class="app-field-control"></label>
        <label class="app-field">Address line 2<input name="address_line_2" value="{{ old('address_line_2', $company->address_line_2 ?? '') }}" class="app-field-control"></label>
        <label class="app-field">Town / city<input id="city" name="city" value="{{ old('city', $company->city ?? '') }}" class="app-field-control"></label>
        <label class="app-field">County<input id="county" name="county" value="{{ old('county', $company->county ?? '') }}" class="app-field-control"></label>
        <label class="app-field">Postcode<input id="postcode" name="postcode" value="{{ old('postcode', $company->postcode ?? '') }}" class="app-field-control"></label>
        <label class="app-field">Country<input id="country" name="country" value="{{ old('country', $company->country ?? 'United Kingdom') }}" class="app-field-control"></label>
    </div>
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2">
    <label class="app-field md:col-span-2">Notes<textarea name="notes" class="app-field-control">{{ old('notes', $company->notes ?? '') }}</textarea></label>
    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->is_active ?? true))> Active</label>
</div>
@if(! isset($company))
    <div class="mt-6 rounded-lg border border-teal-100 bg-teal-50 p-4">
        <h2 class="text-base font-bold text-teal-950">First company admin</h2>
        <p class="mt-1 text-sm text-teal-800">This user will be created with the account and can add their own company users afterward.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="app-field">Admin name<input name="admin_name" value="{{ old('admin_name') }}" class="app-field-control"></label>
            <label class="app-field">Admin email<input name="admin_email" type="email" value="{{ old('admin_email', old('billing_email')) }}" class="app-field-control"></label>
            <label class="app-field">Temporary password<input name="admin_password" type="password" class="app-field-control"></label>
            <label class="app-field">Confirm password<input name="admin_password_confirmation" type="password" class="app-field-control"></label>
        </div>
    </div>
@endif
@if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<button class="app-button mt-6">Save company</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lookupInput = document.getElementById('address-lookup');
        const lookupButton = document.getElementById('postcode-lookup-button');
        const status = document.getElementById('postcode-lookup-status');

        lookupButton?.addEventListener('click', async () => {
            const postcode = lookupInput.value.trim() || document.getElementById('postcode')?.value.trim();

            if (! postcode) {
                status.textContent = 'Enter a postcode to look up.';
                status.className = 'mt-2 text-sm text-amber-700';
                return;
            }

            status.textContent = 'Looking up postcode...';
            status.className = 'mt-2 text-sm text-slate-500';

            try {
                const response = await fetch(`https://api.postcodes.io/postcodes/${encodeURIComponent(postcode)}`);
                const payload = await response.json();

                if (! response.ok || ! payload.result) {
                    throw new Error('Postcode not found');
                }

                document.getElementById('postcode').value = payload.result.postcode || postcode;
                document.getElementById('city').value = payload.result.admin_district || '';
                document.getElementById('county').value = payload.result.admin_county || payload.result.region || '';
                document.getElementById('country').value = payload.result.country || 'United Kingdom';
                status.textContent = 'Postcode found. Add building and street details manually.';
                status.className = 'mt-2 text-sm text-emerald-700';
            } catch (error) {
                status.textContent = 'Could not find that postcode. You can still enter the address manually.';
                status.className = 'mt-2 text-sm text-red-700';
            }
        });
    });
</script>
