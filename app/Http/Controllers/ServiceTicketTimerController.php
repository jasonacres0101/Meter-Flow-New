<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceTicketTimerController extends Controller
{
    public function start(ServiceTicket $serviceTicket, Request $request): RedirectResponse
    {
        $this->authorizeEngineerTimer($serviceTicket, $request);

        $activeLog = $serviceTicket->timeLogs()
            ->where('user_id', $request->user()->id)
            ->whereNull('stopped_at')
            ->exists();

        if ($activeLog) {
            return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Job timer is already running.');
        }

        $serviceTicket->timeLogs()->create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
        ]);

        $serviceTicket->updates()->create([
            'user_id' => $request->user()->id,
            'status' => $serviceTicket->status,
            'notes' => 'Job timer started by engineer.',
        ]);

        return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Job timer started.');
    }

    public function stop(ServiceTicket $serviceTicket, Request $request): RedirectResponse
    {
        $this->authorizeEngineerTimer($serviceTicket, $request);

        $data = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $timeLog = $serviceTicket->timeLogs()
            ->where('user_id', $request->user()->id)
            ->whereNull('stopped_at')
            ->latest('started_at')
            ->first();

        if (! $timeLog) {
            return redirect()->route('service-tickets.show', $serviceTicket)->withErrors(['timer' => 'There is no active job timer to stop.']);
        }

        $stoppedAt = now();
        $durationSeconds = (int) max(0, $timeLog->started_at->diffInSeconds($stoppedAt));

        $timeLog->update([
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $durationSeconds,
            'notes' => $data['notes'] ?? null,
        ]);

        $serviceTicket->updates()->create([
            'user_id' => $request->user()->id,
            'status' => $serviceTicket->status,
            'notes' => 'Job timer stopped. Time logged: '.$this->formatDuration($durationSeconds).".\n\n".$data['notes'],
        ]);

        return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Job time logged.');
    }

    private function authorizeEngineerTimer(ServiceTicket $ticket, Request $request): void
    {
        abort_unless(
            $request->user()->isEngineer()
            && Tenant::activeCompanyId($request->user()) === $ticket->company_id
            && $ticket->assigned_engineer_id === $request->user()->id,
            403,
        );
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }
}
