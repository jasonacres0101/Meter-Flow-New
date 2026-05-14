<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Machine;
use App\Models\Site;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportingService;
use App\Support\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class RevenueReportController extends Controller
{
    public function index(Request $request, ReportingService $reporting): View
    {
        $report = $this->buildReport($request, $reporting);

        return view('reports.revenue', array_merge($report, $this->filterOptions($request)));
    }

    public function export(string $format, Request $request, ReportingService $reporting, ReportExportService $exports): Response
    {
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        $report = $this->buildReport($request, $reporting);

        return match ($format) {
            'csv' => $exports->csv($report),
            'excel' => $exports->excel($report),
            'pdf' => $exports->pdf($report),
        };
    }

    private function buildReport(Request $request, ReportingService $reporting): array
    {
        [$from, $to, $periodLabel] = $this->dateRange($request);
        $scope = $request->string('scope', 'all')->toString();
        $scopeId = $request->integer('scope_id') ?: null;
        $companyId = Tenant::companyId($request->user());
        $scopeLabel = 'All clients';

        $rows = match ($scope) {
            'client' => $this->clientRows($scopeId, $request, $reporting, $from, $to, $scopeLabel),
            'site' => $this->siteRows($scopeId, $request, $reporting, $from, $to, $scopeLabel),
            'machine' => $this->machineRows($scopeId, $request, $reporting, $from, $to, $scopeLabel),
            default => $reporting->revenueBetween($from, $to, $companyId),
        };

        $summary = $reporting->summariseRevenueRows($rows);
        $daily = $rows
            ->groupBy('date')
            ->map(fn ($group, $date) => [
                'date' => $date,
                'mono_pages' => $group->sum('mono_usage'),
                'colour_pages' => $group->sum('colour_usage'),
                'total_pages' => $group->sum('total_usage'),
                'included_pages' => $group->sum('included_total_pages'),
                'chargeable_pages' => $group->sum('chargeable_total_pages'),
                'revenue' => round($group->sum('total_revenue'), 2),
            ])
            ->sortKeys()
            ->values();

        return [
            'from' => $from,
            'to' => $to,
            'period' => $request->string('period', 'current_month')->toString(),
            'period_label' => $periodLabel,
            'scope' => $scope,
            'scope_id' => $scopeId,
            'scope_label' => $scopeLabel,
            'summary' => $summary,
            'rows' => $rows,
            'detailRows' => $this->paginateRows($rows->sortByDesc('date')->values(), $request),
            'daily' => $daily,
            'filename' => str($scopeLabel.' '.$from->format('Ymd').' '.$to->format('Ymd'))->slug('-')->toString(),
        ];
    }

    private function clientRows(?int $scopeId, Request $request, ReportingService $reporting, $from, $to, string &$scopeLabel)
    {
        if (! $scopeId) {
            return $reporting->revenueBetween($from, $to, Tenant::companyId($request->user()));
        }

        $client = Tenant::scope(Client::query(), $request->user())->findOrFail($scopeId);
        $scopeLabel = 'Client: '.$client->name;

        return $reporting->clientRevenue($client, $from, $to);
    }

    private function siteRows(?int $scopeId, Request $request, ReportingService $reporting, $from, $to, string &$scopeLabel)
    {
        if (! $scopeId) {
            return $reporting->revenueBetween($from, $to, Tenant::companyId($request->user()));
        }

        $site = Site::with('client')->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->findOrFail($scopeId);
        $scopeLabel = 'Site: '.$site->client->name.' / '.$site->name;

        return $reporting->siteRevenue($site, $from, $to);
    }

    private function machineRows(?int $scopeId, Request $request, ReportingService $reporting, $from, $to, string &$scopeLabel)
    {
        if (! $scopeId) {
            return $reporting->revenueBetween($from, $to, Tenant::companyId($request->user()));
        }

        $machine = Machine::with(['client', 'site'])->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->findOrFail($scopeId);
        $scopeLabel = 'Machine: '.$machine->machine_name.' / '.$machine->serial_number;

        return $reporting->machineRevenue($machine, $from, $to);
    }

    private function dateRange(Request $request): array
    {
        $today = CarbonImmutable::today();

        return match ($request->string('period', 'current_month')->toString()) {
            'custom' => [
                CarbonImmutable::parse($request->input('from', $today->startOfMonth()->toDateString())),
                CarbonImmutable::parse($request->input('to', $today->toDateString())),
                'Custom date range',
            ],
            'last_30' => [$today->subDays(29), $today, 'Last 30 days'],
            'last_90' => [$today->subDays(89), $today, 'Last 90 days'],
            'previous_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth(), 'Previous month'],
            'quarter_to_date' => [$today->startOfQuarter(), $today, 'Quarter to date'],
            'previous_quarter' => [$today->subQuarter()->startOfQuarter(), $today->subQuarter()->endOfQuarter(), 'Previous quarter'],
            'year_to_date' => [$today->startOfYear(), $today, 'Year to date'],
            'previous_year' => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear(), 'Previous year'],
            default => [$today->startOfMonth(), $today, 'Current month'],
        };
    }

    private function filterOptions(Request $request): array
    {
        return [
            'clients' => Tenant::scope(Client::query(), $request->user())->orderBy('name')->get(),
            'sites' => Site::with('client')->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->orderBy('name')->get(),
            'machines' => Machine::with(['client', 'site'])->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->orderBy('machine_name')->get(),
            'periods' => [
                'current_month' => 'Current month',
                'previous_month' => 'Previous month',
                'quarter_to_date' => 'Quarter to date',
                'previous_quarter' => 'Previous quarter',
                'year_to_date' => 'Year to date',
                'previous_year' => 'Previous year',
                'last_30' => 'Last 30 days',
                'last_90' => 'Last 90 days',
                'custom' => 'Custom dates',
            ],
        ];
    }

    private function paginateRows($rows, Request $request): LengthAwarePaginator
    {
        $perPage = 25;
        $page = max(1, $request->integer('page', 1));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }
}
