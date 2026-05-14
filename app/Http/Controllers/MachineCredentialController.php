<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineCredential;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MachineCredentialController extends Controller
{
    public function store(Request $request, Machine $machine): RedirectResponse
    {
        $this->authorizeMachine($machine, $request);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'last_rotated_at' => ['nullable', 'date'],
        ]);

        $machine->credentials()->create(array_merge($data, ['created_by_user_id' => $request->user()->id]));

        return redirect()->route('machines.show', $machine)->with('status', 'Credential saved securely.');
    }

    public function update(Request $request, Machine $machine, MachineCredential $credential): RedirectResponse
    {
        $this->authorizeMachine($machine, $request);
        abort_unless($credential->machine_id === $machine->id, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'last_rotated_at' => ['nullable', 'date'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $credential->update($data);

        return redirect()->route('machines.show', $machine)->with('status', 'Credential updated securely.');
    }

    public function destroy(Request $request, Machine $machine, MachineCredential $credential): RedirectResponse
    {
        $this->authorizeMachine($machine, $request);
        abort_unless($credential->machine_id === $machine->id, 404);
        $credential->delete();

        return redirect()->route('machines.show', $machine)->with('status', 'Credential deleted.');
    }

    private function authorizeMachine(Machine $machine, Request $request): void
    {
        abort_if($request->user()->isEngineer(), 403);
        abort_unless($request->user()->isPlatformAdmin() || $machine->client->company_id === Tenant::companyId($request->user()), 403);
    }
}
