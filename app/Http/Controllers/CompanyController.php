<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\PlatformMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        return view('companies.index', [
            'companies' => Company::withCount(['users', 'sites', 'machines'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request, PlatformMailer $mailer): RedirectResponse
    {
        $data = $this->validated($request);
        $admin = $this->validatedAccountAdmin($request);

        [$company, $adminUser] = DB::transaction(function () use ($data, $admin) {
            $company = Company::create($data);

            $adminUser = User::create([
                'company_id' => $company->id,
                'name' => $admin['admin_name'],
                'email' => $admin['admin_email'],
                'password' => $admin['admin_password'],
                'role' => User::ROLE_COMPANY_ADMIN,
                'is_active' => true,
            ]);

            return [$company, $adminUser];
        });

        $sent = $mailer->sendAccountCreated($company, $adminUser, $admin['admin_password']);

        return redirect()->route('companies.index')->with('status', $sent
            ? 'Company created and account email sent.'
            : 'Company created. Account email was not sent because outbound email is not configured or failed.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'users' => fn ($query) => $query->orderByRaw('last_login_at is null')->orderByDesc('last_login_at')->orderBy('name'),
            'engineers' => fn ($query) => $query->orderBy('name'),
            'emailSources' => fn ($query) => $query->latest(),
        ]);

        return view('companies.show', [
            'company' => $company->loadCount(['users', 'clients', 'sites', 'machines', 'emailSources']),
            'officeUsers' => $company->users->where('role', '!=', User::ROLE_ENGINEER),
            'engineers' => $company->engineers,
        ]);
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $company->update($this->validated($request, $company));

        return redirect()->route('companies.show', $company)->with('status', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->update(['is_active' => false]);

        return redirect()->route('companies.index')->with('status', 'Company deactivated.');
    }

    private function validated(Request $request, ?Company $company = null): array
    {
        return array_merge($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_reference' => ['nullable', 'string', 'max:255', Rule::unique('companies')->ignore($company)],
            'company_number' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'monthly_machine_rate_override' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]), ['is_active' => $request->boolean('is_active', true)]);
    }

    private function validatedAccountAdmin(Request $request): array
    {
        return $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
