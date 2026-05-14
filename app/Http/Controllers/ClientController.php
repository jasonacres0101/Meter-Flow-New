<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Reports\ReportingService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', ['clients' => Tenant::scope(Client::query(), request()->user())->withCount(['sites', 'machines'])->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $this->authorizeWrite($request);

        return view('clients.create', ['client' => new Client(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWrite($request);

        $client = Client::create(array_merge($this->validated($request), [
            'company_id' => Tenant::companyId($request->user()),
            'is_active' => $request->boolean('is_active', true),
        ]));

        if ($request->input('after_save') === 'add_site') {
            return redirect()->route('sites.create', ['client_id' => $client->id])->with('status', 'Client created. Add the first site for this client.');
        }

        return redirect()->route('clients.show', $client)->with('status', 'Client created.');
    }

    public function show(Client $client, Request $request, ReportingService $reporting): View
    {
        abort_unless($request->user()->isPlatformAdmin() || $client->company_id === Tenant::companyId($request->user()), 403);

        $days = (int) $request->integer('days', 30);
        $from = today()->subDays($days - 1);
        $client->load(['sites.machines', 'machines.site']);
        $revenueRows = $reporting->clientRevenue($client, $from, today());

        $revenue = $reporting->summariseRevenueRows($revenueRows);

        return view('clients.show', [
            'client' => $client,
            'usage' => $reporting->clientDailyUsage($client, $from, today())->values(),
            'revenue' => $revenue,
            'dailyRevenue' => $revenue['rows']->groupBy('date')->map(fn ($rows) => round($rows->sum('total_revenue'), 2))->sortKeys()->values(),
            'days' => $days,
        ]);
    }

    public function edit(Client $client, Request $request): View
    {
        $this->authorizeWrite($request);
        $this->authorizeTenant($client, $request);

        return view('clients.edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeWrite($request);
        $this->authorizeTenant($client, $request);

        $client->update(array_merge($this->validated($request), [
            'is_active' => $request->boolean('is_active'),
        ]));

        return redirect()->route('clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        abort(404);
    }

    private function authorizeTenant(Client $client, Request $request): void
    {
        abort_unless($request->user()->isPlatformAdmin() || $client->company_id === Tenant::companyId($request->user()), 403);
    }

    private function authorizeWrite(Request $request): void
    {
        abort_if($request->user()->isPlatformAdmin() || $request->user()->isEngineer(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_reference' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
