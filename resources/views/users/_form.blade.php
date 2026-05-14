@csrf
<div class="grid gap-4 md:grid-cols-2">
    <label class="app-field">Name<input name="name" value="{{ old('name', $managedUser->name ?? '') }}" class="app-field-control"></label>
    <label class="app-field">Email<input name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" class="app-field-control"></label>
    @if(auth()->user()->isPlatformAdmin())
        <label class="app-field">Company<select name="company_id" class="app-field-control"><option value="">Platform</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id', $managedUser->company_id ?? request('company_id')) == $company->id)>{{ $company->name }}</option>@endforeach</select></label>
    @else
        <label class="app-field">Company
            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            <span class="app-field-control flex items-center" aria-readonly="true">{{ auth()->user()->company?->name }}</span>
        </label>
    @endif
    <label class="app-field">Role<select name="role" class="app-field-control">@foreach($roles as $role)<option value="{{ $role }}" @selected(old('role', $managedUser->role ?? 'company_user') === $role)>{{ str_replace('_', ' ', $role) }}</option>@endforeach</select></label>
    <label class="app-field">Password<input name="password" type="password" class="app-field-control"></label>
    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser->is_active ?? true))> Active</label>
</div>
@if(isset($managedUser) && auth()->user()->isPlatformAdmin())
    <div class="mt-5 rounded-lg border border-amber-100 bg-amber-50 p-4">
        <h2 class="text-base font-bold text-amber-950">Support reset tools</h2>
        <p class="mt-1 text-sm text-amber-800">Use these when helping a user regain access. Passwords and engineer PINs are stored hashed.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="app-field">New engineer PIN<input name="engineer_pin" type="password" inputmode="numeric" class="app-field-control" placeholder="4-8 digits"></label>
            <label class="app-field">Confirm engineer PIN<input name="engineer_pin_confirmation" type="password" inputmode="numeric" class="app-field-control"></label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 md:col-span-2"><input type="checkbox" name="clear_engineer_pin" value="1"> Clear engineer PIN</label>
        </div>
    </div>
@endif
<p class="mt-3 rounded-lg bg-blue-50 p-3 text-sm text-blue-800">Engineer users are matched by email. If an engineer already exists, adding the same email links that engineer to this company instead of creating a duplicate login.</p>
@if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<button class="app-button mt-6">Save user</button>
