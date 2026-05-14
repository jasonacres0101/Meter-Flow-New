<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\ServiceTicketEngineerOffer;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceTicketAcceptanceController extends Controller
{
    public function store(ServiceTicket $serviceTicket, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isEngineer(), 403);
        abort_unless(Tenant::activeCompanyId($request->user()) === $serviceTicket->company_id, 403);

        $accepted = DB::transaction(function () use ($serviceTicket, $request): bool {
            $ticket = ServiceTicket::whereKey($serviceTicket->id)->lockForUpdate()->firstOrFail();

            if ($ticket->assigned_engineer_id && $ticket->assigned_engineer_id !== $request->user()->id) {
                return false;
            }

            $offer = ServiceTicketEngineerOffer::query()
                ->where('service_ticket_id', $ticket->id)
                ->where('user_id', $request->user()->id)
                ->whereNull('withdrawn_at')
                ->whereNull('declined_at')
                ->first();

            if (! $offer && $ticket->assigned_engineer_id !== $request->user()->id) {
                abort(403);
            }

            $ticket->update([
                'assigned_engineer_id' => $request->user()->id,
            ]);

            if ($offer) {
                $offer->update(['accepted_at' => now()]);
            }

            ServiceTicketEngineerOffer::query()
                ->where('service_ticket_id', $ticket->id)
                ->where('user_id', '!=', $request->user()->id)
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);

            $ticket->updates()->create([
                'user_id' => $request->user()->id,
                'status' => $ticket->status,
                'notes' => 'Ticket accepted by engineer.',
            ]);

            return true;
        });

        if (! $accepted) {
            return redirect()->route('service-tickets.index')->withErrors(['ticket' => 'This ticket has already been accepted by another engineer.']);
        }

        return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Ticket accepted and assigned to you.');
    }
}
