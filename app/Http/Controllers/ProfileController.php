<?php

namespace App\Http\Controllers;

use App\Models\EngineerSkillProfile;
use App\Models\Manufacturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load(['engineerSkillProfile', 'supportedManufacturers']),
            'manufacturers' => Manufacturer::where('is_active', true)->orderBy('name')->get(),
            'skillLevels' => EngineerSkillProfile::LEVELS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'skills' => ['nullable', 'array'],
            'skills.networking_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.vlan_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.dhcp_static_ip_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.dns_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.routing_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.firewall_level' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
            'skills.notes' => ['nullable', 'string'],
            'manufacturer_skills' => ['nullable', 'array'],
            'manufacturer_skills.*' => ['nullable', Rule::in(array_keys(EngineerSkillProfile::LEVELS))],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update(collect($data)->only(['name', 'email', 'password'])->all());

        if ($user->isEngineer()) {
            $skills = collect($data['skills'] ?? [])->map(fn ($value) => $value === '' ? null : $value)->all();
            $user->engineerSkillProfile()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge([
                    'networking_level' => EngineerSkillProfile::LEVEL_NONE,
                    'vlan_level' => EngineerSkillProfile::LEVEL_NONE,
                    'dhcp_static_ip_level' => EngineerSkillProfile::LEVEL_NONE,
                    'dns_level' => EngineerSkillProfile::LEVEL_NONE,
                    'routing_level' => EngineerSkillProfile::LEVEL_NONE,
                    'firewall_level' => EngineerSkillProfile::LEVEL_NONE,
                ], $skills),
            );

            $manufacturerSync = collect($data['manufacturer_skills'] ?? [])
                ->filter(fn ($level) => filled($level))
                ->mapWithKeys(fn ($level, $manufacturerId) => [(int) $manufacturerId => ['skill_level' => $level]])
                ->all();

            $validManufacturerIds = Manufacturer::whereIn('id', array_keys($manufacturerSync))->pluck('id')->all();
            $user->supportedManufacturers()->sync(collect($manufacturerSync)->only($validManufacturerIds)->all());
        }

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
