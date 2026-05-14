<?php

namespace App\Http\Controllers;

use App\Models\ConsumableReading;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MeterReading;
use App\Models\ServiceTicket;
use App\Services\Reports\ReportingService;
use App\Services\TonerAlertService;
use App\Support\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(ReportingService $reporting, TonerAlertService $tonerAlerts, Request $request)
    {
        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('companies.index');
        }

        if ($request->user()->isEngineer()) {
            return $this->engineerDashboard($request);
        }

        $today = today();
        $companyId = Tenant::companyId($request->user());
        $tonerSetting = $tonerAlerts->settingFor($companyId);
        $usageThisMonth = $reporting->usageBetween(today()->startOfMonth(), today(), $companyId);

        return view('dashboard', [
            'activeMachines' => Tenant::scope(Machine::query()->join('clients', 'clients.id', '=', 'machines.client_id')->where('machines.is_active', true), $request->user(), 'clients.company_id')->count(),
            'reportsToday' => Tenant::scope(IncomingReportEmail::query(), $request->user())->whereDate('received_at', $today)->count(),
            'missingToday' => Tenant::scope(Machine::query()->where('machines.is_active', true)->join('clients', 'clients.id', '=', 'machines.client_id')->select('machines.*'), $request->user(), 'clients.company_id')
                ->whereDoesntHave('incomingReportEmails', fn ($query) => $query->whereDate('received_at', $today))
                ->with(['client', 'site'])
                ->get(),
            'unmatchedEmails' => Tenant::scope(IncomingReportEmail::query(), $request->user())->where('parse_status', IncomingReportEmail::STATUS_UNMATCHED)->count(),
            'failedParses' => Tenant::scope(IncomingReportEmail::query(), $request->user())->where('parse_status', IncomingReportEmail::STATUS_FAILED)->count(),
            'latestReadings' => Tenant::scope(MeterReading::query(), $request->user())->with('machine.client')->latest('reading_date')->limit(10)->get(),
            'lowTonerAlerts' => $tonerSetting->include_in_dashboard
                ? Tenant::scope(ConsumableReading::query(), $request->user())->with('machine.client')->where('consumable_type', 'toner')->where('percentage', '<=', $tonerSetting->warning_threshold)->latest('reading_date')->limit(10)->get()
                : collect(),
            'totalPagesToday' => $reporting->usageBetween($today, $today, $companyId)->sum('total_usage'),
            'totalPagesMonth' => $usageThisMonth->sum('total_usage'),
            'topMachines' => $usageThisMonth->groupBy('machine_id')->map->sum('total_usage')->sortDesc()->take(10),
        ]);
    }

    private function engineerDashboard(Request $request)
    {
        $companyId = Tenant::companyId($request->user());
        $ticketBase = ServiceTicket::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($request) {
                $query->where('assigned_engineer_id', $request->user()->id)
                    ->orWhere(function ($query) use ($request) {
                        $query->whereNull('assigned_engineer_id')
                            ->whereHas('engineerOffers', fn ($offerQuery) => $offerQuery
                                ->where('user_id', $request->user()->id)
                                ->whereNull('withdrawn_at')
                                ->whereNull('declined_at'));
                    });
            });

        $waitingAcceptance = (clone $ticketBase)
            ->with(['site', 'machine'])
            ->whereNull('assigned_engineer_id')
            ->latest()
            ->limit(8)
            ->get();

        $assignedTickets = (clone $ticketBase)
            ->with(['client', 'site', 'machine'])
            ->where('assigned_engineer_id', $request->user()->id)
            ->whereNotIn('status', [ServiceTicket::STATUS_RESOLVED, ServiceTicket::STATUS_CLOSED])
            ->latest()
            ->limit(8)
            ->get();

        $openStatuses = [ServiceTicket::STATUS_OPEN, ServiceTicket::STATUS_SCHEDULED, ServiceTicket::STATUS_IN_PROGRESS];
        $closedStatuses = [ServiceTicket::STATUS_RESOLVED, ServiceTicket::STATUS_CLOSED];

        return view('dashboards.engineer', [
            'waitingAcceptance' => $waitingAcceptance,
            'assignedTickets' => $assignedTickets,
            'waitingAcceptanceCount' => (clone $ticketBase)->whereNull('assigned_engineer_id')->count(),
            'openTicketCount' => (clone $ticketBase)->where('assigned_engineer_id', $request->user()->id)->whereIn('status', $openStatuses)->count(),
            'closedTicketCount' => (clone $ticketBase)->where('assigned_engineer_id', $request->user()->id)->whereIn('status', $closedStatuses)->count(),
            'openMachineCount' => (clone $ticketBase)->where('assigned_engineer_id', $request->user()->id)->whereIn('status', $openStatuses)->distinct('machine_id')->count('machine_id'),
            'areaCounts' => (clone $ticketBase)
                ->whereNull('assigned_engineer_id')
                ->join('sites', 'sites.id', '=', 'service_tickets.site_id')
                ->selectRaw("COALESCE(NULLIF(sites.city, ''), 'Unknown') as area, count(*) as total")
                ->groupBy('area')
                ->orderBy('area')
                ->pluck('total', 'area'),
        ]);
    }
}
