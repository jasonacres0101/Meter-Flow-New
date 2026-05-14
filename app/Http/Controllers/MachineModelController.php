<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineModelRequest;
use App\Models\Manufacturer;
use App\Models\MachineModel;
use App\Support\ParserRegistry;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MachineModelController extends Controller
{
    public function index(): View
    {
        $query = MachineModel::query();

        if (request()->user()->isPlatformAdmin()) {
            $query->whereNull('company_id');
        } else {
            Tenant::scopeWithGlobal($query, request()->user());
        }

        return view('machine-models.index', ['machineModels' => $query->with(['company', 'manufacturerRecord'])->withCount(['machines', 'reportTemplates'])->paginate(20)]);
    }

    public function create(): View
    {
        return view('machine-models.create', $this->formData());
    }

    public function store(StoreMachineModelRequest $request): RedirectResponse
    {
        $machineModel = MachineModel::create(array_merge($this->normalise($request), ['company_id' => Tenant::companyId($request->user())]));

        return redirect()->route('machine-models.show', $machineModel)->with('status', 'Machine model created.');
    }

    public function show(MachineModel $machineModel): View
    {
        $this->authorizeTenant($machineModel);

        $machineModel->load(request()->user()->isPlatformAdmin() ? ['manufacturerRecord', 'reportTemplates'] : ['manufacturerRecord', 'machines.client', 'reportTemplates']);

        return view('machine-models.show', ['machineModel' => $machineModel]);
    }

    public function edit(MachineModel $machineModel): View
    {
        $this->authorizeTenant($machineModel);
        abort_if(! request()->user()->isPlatformAdmin() && is_null($machineModel->company_id), 403);

        return view('machine-models.edit', array_merge($this->formData(), ['machineModel' => $machineModel]));
    }

    public function update(StoreMachineModelRequest $request, MachineModel $machineModel): RedirectResponse
    {
        $this->authorizeTenant($machineModel);
        abort_if(! $request->user()->isPlatformAdmin() && is_null($machineModel->company_id), 403);
        $machineModel->update($this->normalise($request));

        return redirect()->route('machine-models.show', $machineModel)->with('status', 'Machine model updated.');
    }

    public function destroy(MachineModel $machineModel): RedirectResponse
    {
        $this->authorizeTenant($machineModel);
        abort_if(! request()->user()->isPlatformAdmin() && is_null($machineModel->company_id), 403);
        $machineModel->delete();

        return redirect()->route('machine-models.index')->with('status', 'Machine model deleted.');
    }

    private function formData(): array
    {
        return [
            'manufacturers' => Manufacturer::query()->where('is_active', true)->orderBy('name')->get(),
            'parserTypes' => ParserRegistry::options(),
        ];
    }

    private function normalise(StoreMachineModelRequest $request): array
    {
        $data = $request->validated();
        $manufacturer = filled($data['manufacturer_name'] ?? null)
            ? Manufacturer::findOrCreateByName($data['manufacturer_name'])
            : Manufacturer::findOrFail($data['manufacturer_id']);

        unset($data['manufacturer_name']);

        return array_merge($data, [
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
        ]);
    }

    private function authorizeTenant(MachineModel $machineModel): void
    {
        abort_unless(
            (request()->user()->isPlatformAdmin() && is_null($machineModel->company_id))
            || (! request()->user()->isPlatformAdmin() && (is_null($machineModel->company_id) || $machineModel->company_id === request()->user()->company_id)),
            403
        );
    }
}
