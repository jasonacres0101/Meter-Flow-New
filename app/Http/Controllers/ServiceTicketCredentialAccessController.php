<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceTicketCredentialAccessController extends Controller
{
    public function store(Request $request, ServiceTicket $serviceTicket): RedirectResponse
    {
        $this->authorizeTicket($serviceTicket, $request);

        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,8'],
        ]);

        if (! $request->user()->engineerPinMatches($data['pin'])) {
            return back()->withErrors(['pin' => 'The engineer PIN is incorrect.']);
        }

        session()->put($this->sessionKey($serviceTicket, $request), now()->toIso8601String());

        return back()->with('status', 'Machine credentials unlocked for this ticket.');
    }

    public function destroy(Request $request, ServiceTicket $serviceTicket): RedirectResponse
    {
        $this->authorizeTicket($serviceTicket, $request);
        session()->forget($this->sessionKey($serviceTicket, $request));

        return back()->with('status', 'Machine credentials locked.');
    }

    private function authorizeTicket(ServiceTicket $ticket, Request $request): void
    {
        abort_unless(
            $request->user()->isEngineer()
            && Tenant::activeCompanyId($request->user()) === $ticket->company_id
            && $ticket->assigned_engineer_id === $request->user()->id,
            403,
        );
    }

    private function sessionKey(ServiceTicket $ticket, Request $request): string
    {
        return 'unlocked_ticket_credentials.'.$request->user()->id.'.'.$ticket->id;
    }
}
