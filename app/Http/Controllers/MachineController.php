<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineRequest;
use App\Models\Client;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\Site;
use App\Services\Reports\PendingReportReprocessor;
use App\Services\Reports\ReportingService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineController extends Controller
{
    public function index(): View
    {
        $machines = Machine::query()->join('clients', 'clients.id', '=', 'machines.client_id')->select('machines.*');

        return view('machines.index', [
            'machines' => Tenant::scope($machines, request()->user(), 'clients.company_id')->with(['client', 'site', 'machineModel'])->latest('machines.created_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        abort_if(request()->user()->isEngineer(), 403);

        return view('machines.create', array_merge($this->formData(), [
            'machine' => new Machine([
                'client_id' => request()->integer('client_id') ?: null,
                'site_id' => request()->integer('site_id') ?: null,
                'is_active' => true,
            ]),
        ]));
    }

    public function store(StoreMachineRequest $request, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        abort_if($request->user()->isEngineer(), 403);

        $machine = Machine::create($this->normalisedMachineData($request));
        $matched = $reprocessor->forMachine($machine)->count();

        return redirect()->route('machines.index')->with('status', $matched > 0 ? "Machine created. {$matched} stored report email(s) matched to it." : 'Machine created.');
    }

    public function show(Machine $machine, Request $request, ReportingService $reporting): View
    {
        $this->authorizeTenant($machine);

        $days = (int) $request->integer('days', 30);
        $from = today()->subDays($days - 1);
        $machine->load(['client', 'site', 'machineModel', 'credentials.createdBy', 'serviceTickets.assignedEngineer', 'incomingReportEmails' => fn ($query) => $query->latest('received_at')->limit(20)]);
        $revenueRows = $reporting->machineRevenue($machine, $from, today());

        $revenue = $reporting->summariseRevenueRows($revenueRows);
        $toners = $machine->consumableReadings()->where('consumable_type', 'toner')->latest('reading_date')->get()->unique('colour');
        $latestLifecycleEmail = $machine->incomingReportEmails()
            ->whereNotNull('parsed_payload')
            ->latest('received_at')
            ->get()
            ->first(fn (IncomingReportEmail $email) => collect(data_get($email->parsed_payload, 'raw.toner_lifecycle.inserted_toner_number', []))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->isNotEmpty());
        $insertedTonerNumbers = data_get($latestLifecycleEmail?->parsed_payload, 'raw.toner_lifecycle.inserted_toner_number', []);

        return view('machines.show', [
            'machine' => $machine,
            'latestReading' => $machine->meterReadings()->latest('reading_date')->first(),
            'usage' => $reporting->machineDailyUsage($machine, $from, today())->values(),
            'revenue' => $revenue,
            'dailyRevenue' => $revenue['rows']->groupBy('date')->map(fn ($rows) => round($rows->sum('total_revenue'), 2))->sortKeys()->values(),
            'toners' => $toners,
            'tonerValues' => $toners->pluck('percentage', 'colour'),
            'tonerLabels' => $toners->pluck('colour')->map(fn ($colour) => ucfirst($colour))->values(),
            'insertedTonerNumbers' => $insertedTonerNumbers,
            'insertedTonerReport' => $latestLifecycleEmail,
            'parseErrors' => $machine->incomingReportEmails()->whereNotNull('parse_error')->latest()->limit(10)->get(),
            'days' => $days,
        ]);
    }

    public function edit(Machine $machine): View
    {
        abort_if(request()->user()->isEngineer(), 403);
        $this->authorizeTenant($machine);

        return view('machines.edit', array_merge($this->formData(), ['machine' => $machine]));
    }

    public function update(StoreMachineRequest $request, Machine $machine, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        abort_if($request->user()->isEngineer(), 403);
        $this->authorizeTenant($machine);
        $machine->update($this->normalisedMachineData($request));
        $matched = $reprocessor->forMachine($machine->refresh())->count();

        return redirect()->route('machines.show', $machine)->with('status', $matched > 0 ? "Machine updated. {$matched} stored report email(s) matched to it." : 'Machine updated.');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        abort_if(request()->user()->isEngineer(), 403);
        $this->authorizeTenant($machine);
        $machine->delete();

        return redirect()->route('machines.index')->with('status', 'Machine deleted.');
    }

    private function formData(): array
    {
        $user = request()->user();

        return [
            'clients' => Tenant::scope(Client::query(), $user)->orderBy('name')->get(),
            'sites' => Site::with('client')->whereHas('client', fn ($query) => Tenant::scope($query, $user))->orderBy('name')->get(),
            'machineModels' => Tenant::scopeWithGlobal(MachineModel::query(), $user)->with('manufacturerRecord')->orderBy('manufacturer')->orderBy('model_name')->get(),
            'manufacturers' => Manufacturer::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function normalisedMachineData(StoreMachineRequest $request): array
    {
        $data = $request->validated();

        $machineModel = MachineModel::query()->with('manufacturerRecord')->findOrFail($data['machine_model_id']);
        $manufacturer = $machineModel->manufacturerRecord
            ?? (filled($data['manufacturer_id'] ?? null) ? Manufacturer::query()->find($data['manufacturer_id']) : null);

        if (! $request->user()->isPlatformAdmin()) {
            abort_unless(Client::whereKey($data['client_id'])->where('company_id', $request->user()->company_id)->exists(), 403);
            abort_unless(MachineModel::whereKey($data['machine_model_id'])
                ->where(fn ($query) => $query->where('company_id', $request->user()->company_id)->orWhereNull('company_id'))
                ->exists(), 403);
        }

        unset($data['manufacturer_id']);

        return array_merge($data, [
            'manufacturer' => $manufacturer?->name ?? $machineModel->manufacturer,
            'model' => $machineModel->model_name,
            'dhcp_enabled' => $request->boolean('dhcp_enabled'),
            'is_active' => $request->boolean('is_active'),
        ]);
    }

    private function authorizeTenant(Machine $machine): void
    {
        abort_unless(request()->user()->isPlatformAdmin() || $machine->client->company_id === Tenant::companyId(request()->user()), 403);
    }
}
