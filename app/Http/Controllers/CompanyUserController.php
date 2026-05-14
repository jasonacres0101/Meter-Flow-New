<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['company', 'engineerCompanies'])->orderBy('name');

        if (! $request->user()->isPlatformAdmin()) {
            $query->where(function ($query) use ($request) {
                $query->where('company_id', $request->user()->company_id)
                    ->orWhereHas('engineerCompanies', fn ($engineerQuery) => $engineerQuery->whereKey($request->user()->company_id));
            });
        }

        return view('users.index', ['users' => $query->paginate(20)]);
    }

    public function create(Request $request): View
    {
        return view('users.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $this->companyIdFor($request, $data['company_id'] ?? null);
        $data['role'] = $this->roleFor($request, $data['role']);
        unset($data['engineer_pin'], $data['clear_engineer_pin']);

        if ($data['role'] === User::ROLE_ENGINEER && $engineer = User::where('email', $data['email'])->first()) {
            abort_unless($engineer->isEngineer(), 422, 'That email already belongs to a non-engineer user.');
            $engineer->engineerCompanies()->syncWithoutDetaching([$data['company_id']]);

            return redirect()->route('users.show', $engineer)->with('status', 'Existing engineer linked to this company.');
        }

        $user = User::create($data);

        if ($user->isEngineer() && $data['company_id']) {
            $user->engineerCompanies()->syncWithoutDetaching([$data['company_id']]);
        }

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function show(User $user): View
    {
        $this->authorizeUserAccess($user);

        return view('users.show', ['managedUser' => $user->load(['company', 'engineerCompanies', 'engineerSkillProfile', 'supportedManufacturers'])]);
    }

    public function edit(User $user): View
    {
        $this->authorizeUserAccess($user);

        return view('users.edit', array_merge($this->formData(request()), ['managedUser' => $user]));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);
        $data = $this->validated($request, $user);
        $data['company_id'] = $this->companyIdFor($request, $data['company_id'] ?? $user->company_id);
        $data['role'] = $this->roleFor($request, $data['role']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($request->boolean('clear_engineer_pin')) {
            $data['engineer_pin'] = null;
        } elseif (filled($data['engineer_pin'] ?? null)) {
            $data['engineer_pin'] = bcrypt($data['engineer_pin']);
        } else {
            unset($data['engineer_pin']);
        }

        unset($data['clear_engineer_pin']);

        $user->update($data);

        return redirect()->route('users.show', $user)->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUserAccess($user);
        abort_if($user->is(request()->user()), 422, 'You cannot deactivate your own account.');

        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('status', 'User deactivated.');
    }

    private function formData(Request $request): array
    {
        return [
            'companies' => $request->user()->isPlatformAdmin() ? Company::orderBy('name')->get() : collect([$request->user()->company]),
            'roles' => $request->user()->isPlatformAdmin()
                ? [User::ROLE_PLATFORM_ADMIN, User::ROLE_COMPANY_ADMIN, User::ROLE_COMPANY_USER, User::ROLE_ENGINEER]
                : [User::ROLE_COMPANY_ADMIN, User::ROLE_COMPANY_USER, User::ROLE_ENGINEER],
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user)->where(fn ($query) => $request->input('role') !== User::ROLE_ENGINEER ? $query : $query->where('role', '!=', User::ROLE_ENGINEER)),
            ],
            'role' => ['required', Rule::in([User::ROLE_PLATFORM_ADMIN, User::ROLE_COMPANY_ADMIN, User::ROLE_COMPANY_USER, User::ROLE_ENGINEER])],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'engineer_pin' => ['nullable', 'digits_between:4,8', 'confirmed'],
            'clear_engineer_pin' => ['nullable', 'boolean'],
        ]);
    }

    private function companyIdFor(Request $request, ?int $companyId): ?int
    {
        return $request->user()->isPlatformAdmin() ? $companyId : $request->user()->company_id;
    }

    private function roleFor(Request $request, string $role): string
    {
        return $request->user()->isPlatformAdmin() ? $role : ($role === User::ROLE_PLATFORM_ADMIN ? User::ROLE_COMPANY_USER : $role);
    }

    private function authorizeUserAccess(User $user): void
    {
        abort_unless(
            request()->user()->isPlatformAdmin()
            || request()->user()->company_id === $user->company_id
            || ($user->isEngineer() && $user->engineerCompanies()->whereKey(request()->user()->company_id)->exists()),
            403,
        );
    }
}
