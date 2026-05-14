<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ServiceAgreement;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceAgreementController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Tenant::companyId($request->user());
        $agreements = ServiceAgreement::query()
            ->with(['machines.client', 'machines.site'])
            ->where('company_id', $companyId)
            ->orderByDesc('is_active')
            ->orderByDesc('starts_on')
            ->paginate(15);

        $coveredMachineIds = ServiceAgreement::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()))
            ->whereHas('machines')
            ->with('machines:id')
            ->get()
            ->flatMap->machines
            ->pluck('id')
            ->unique();

        $machineCount = Machine::query()
            ->whereHas('client', fn ($query) => $query->where('company_id', $companyId))
            ->count();

        return view('service-agreements.index', [
            'agreements' => $agreements,
            'activeAgreementCount' => ServiceAgreement::where('company_id', $companyId)->where('is_active', true)->count(),
            'coveredMachineCount' => $coveredMachineIds->count(),
            'uncoveredMachineCount' => max(0, $machineCount - $coveredMachineIds->count()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('service-agreements.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $agreement = ServiceAgreement::create($this->agreementAttributes($request, $data));
        $agreement->machines()->sync($data['machine_ids']);

        return redirect()->route('service-agreements.show', $agreement)->with('status', 'Service agreement created.');
    }

    public function show(ServiceAgreement $serviceAgreement, Request $request): View
    {
        $this->authorizeTenant($serviceAgreement, $request);
        $serviceAgreement->load(['machines.client', 'machines.site']);

        return view('service-agreements.show', [
            'agreement' => $serviceAgreement,
        ]);
    }

    public function edit(ServiceAgreement $serviceAgreement, Request $request): View
    {
        $this->authorizeTenant($serviceAgreement, $request);
        $serviceAgreement->load('machines');

        return view('service-agreements.edit', array_merge(
            $this->formData($request),
            ['agreement' => $serviceAgreement],
        ));
    }

    public function update(ServiceAgreement $serviceAgreement, Request $request): RedirectResponse
    {
        $this->authorizeTenant($serviceAgreement, $request);
        $data = $this->validated($request, $serviceAgreement);

        $serviceAgreement->update($this->agreementAttributes($request, $data));
        $serviceAgreement->machines()->sync($data['machine_ids']);

        return redirect()->route('service-agreements.show', $serviceAgreement)->with('status', 'Service agreement updated.');
    }

    public function destroy(ServiceAgreement $serviceAgreement, Request $request): RedirectResponse
    {
        $this->authorizeTenant($serviceAgreement, $request);
        $serviceAgreement->delete();

        return redirect()->route('service-agreements.index')->with('status', 'Service agreement deleted.');
    }

    private function formData(Request $request): array
    {
        $companyId = Tenant::companyId($request->user());

        return [
            'machines' => Machine::query()
                ->with(['client', 'site', 'agreements' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereDate('starts_on', '<=', now())
                    ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()))])
                ->whereHas('client', fn ($query) => $query->where('company_id', $companyId))
                ->orderBy('machine_name')
                ->get(),
        ];
    }

    private function validated(Request $request, ?ServiceAgreement $agreement = null): array
    {
        $companyId = Tenant::companyId($request->user());
        $machineIds = Machine::query()
            ->whereHas('client', fn ($query) => $query->where('company_id', $companyId))
            ->pluck('id')
            ->all();

        return $request->validate([
            'agreement_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_agreements')
                    ->where('company_id', $companyId)
                    ->ignore($agreement?->id),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'mono_ppc' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'colour_ppc' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'included_mono_pages' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'included_colour_pages' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'is_active' => ['nullable', 'boolean'],
            'machine_ids' => ['required', 'array', 'min:1'],
            'machine_ids.*' => ['integer', Rule::in($machineIds)],
        ]);
    }

    private function agreementAttributes(Request $request, array $data): array
    {
        return [
            'company_id' => Tenant::companyId($request->user()),
            'client_id' => null,
            'site_id' => null,
            'machine_id' => null,
            'agreement_number' => $data['agreement_number'],
            'starts_on' => Carbon::parse($data['starts_on'])->toDateString(),
            'ends_on' => filled($data['ends_on'] ?? null) ? Carbon::parse($data['ends_on'])->toDateString() : null,
            'mono_ppc' => $this->blankToNull($data['mono_ppc'] ?? null),
            'colour_ppc' => $this->blankToNull($data['colour_ppc'] ?? null),
            'included_mono_pages' => $this->blankToNull($data['included_mono_pages'] ?? null),
            'included_colour_pages' => $this->blankToNull($data['included_colour_pages'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    private function authorizeTenant(ServiceAgreement $agreement, Request $request): void
    {
        abort_unless($agreement->company_id === Tenant::companyId($request->user()), 403);
    }
}
