<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Site;
use App\Services\Reports\ReportingService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        return view('sites.index', ['sites' => Site::with('client')->whereHas('client', fn ($query) => Tenant::scope($query, request()->user()))->withCount('machines')->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $this->authorizeWrite($request);

        return view('sites.create', [
            'site' => new Site(['is_active' => true]),
            'clients' => $this->clientsForUser($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request);
        $client = Client::query()->whereKey($data['client_id'])->where('company_id', Tenant::companyId($request->user()))->firstOrFail();

        $site = $client->sites()->create(array_merge($data, [
            'is_active' => $request->boolean('is_active', true),
        ]));

        if ($request->input('after_save') === 'add_machine') {
            return redirect()->route('machines.create', ['client_id' => $client->id, 'site_id' => $site->id])->with('status', 'Site created. Add the first machine for this site.');
        }

        return redirect()->route('sites.show', $site)->with('status', 'Site created.');
    }

    public function show(Site $site, Request $request, ReportingService $reporting): View
    {
        abort_unless($request->user()->isPlatformAdmin() || $site->client->company_id === Tenant::companyId($request->user()), 403);

        $days = (int) $request->integer('days', 30);
        $from = today()->subDays($days - 1);
        $site->load(['client', 'machines.machineModel']);
        $revenueRows = $reporting->siteRevenue($site, $from, today());

        $revenue = $reporting->summariseRevenueRows($revenueRows);

        return view('sites.show', [
            'site' => $site,
            'usage' => $reporting->siteDailyUsage($site, $from, today())->values(),
            'revenue' => $revenue,
            'dailyRevenue' => $revenue['rows']->groupBy('date')->map(fn ($rows) => round($rows->sum('total_revenue'), 2))->sortKeys()->values(),
            'days' => $days,
        ]);
    }

    public function edit(Site $site, Request $request): View
    {
        $this->authorizeWrite($request);
        $this->authorizeTenant($site, $request);

        return view('sites.edit', [
            'site' => $site,
            'clients' => $this->clientsForUser($request),
        ]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeWrite($request);
        $this->authorizeTenant($site, $request);
        $data = $this->validated($request);
        Client::query()->whereKey($data['client_id'])->where('company_id', Tenant::companyId($request->user()))->firstOrFail();

        $site->update(array_merge($data, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return redirect()->route('sites.show', $site)->with('status', 'Site updated.');
    }

    public function destroy(Site $site)
    {
        abort(404);
    }

    private function authorizeTenant(Site $site, Request $request): void
    {
        abort_unless($request->user()->isPlatformAdmin() || $site->client->company_id === Tenant::companyId($request->user()), 403);
    }

    private function authorizeWrite(Request $request): void
    {
        abort_if($request->user()->isPlatformAdmin() || $request->user()->isEngineer(), 403);
    }

    private function clientsForUser(Request $request)
    {
        return Tenant::scope(Client::query(), $request->user())->orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
