<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Machine;
use App\Models\Site;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $clients = Tenant::scope(Client::query(), $request->user())->with(['sites.machines'])->orderBy('name')->get();

        return view('pricing-settings.edit', [
            'clients' => $clients,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clients' => ['nullable', 'array'],
            'clients.*.mono_ppc' => ['required', 'numeric', 'min:0', 'max:9999'],
            'clients.*.colour_ppc' => ['required', 'numeric', 'min:0', 'max:9999'],
            'clients.*.included_mono_pages' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'clients.*.included_colour_pages' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'sites' => ['nullable', 'array'],
            'sites.*.mono_ppc_override' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'sites.*.colour_ppc_override' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'sites.*.included_mono_pages_override' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'sites.*.included_colour_pages_override' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'machines' => ['nullable', 'array'],
            'machines.*.mono_ppc_override' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'machines.*.colour_ppc_override' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'machines.*.included_mono_pages_override' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'machines.*.included_colour_pages_override' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ]);

        foreach ($data['clients'] ?? [] as $clientId => $prices) {
            $client = Tenant::scope(Client::query(), $request->user())->findOrFail($clientId);
            $client->update($this->modelPrices($prices, 'client'));
        }

        foreach ($data['sites'] ?? [] as $siteId => $prices) {
            $site = Site::whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->findOrFail($siteId);
            $site->update($this->modelPrices($prices, 'site'));
        }

        foreach ($data['machines'] ?? [] as $machineId => $prices) {
            $machine = Machine::whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->findOrFail($machineId);
            $machine->update($this->modelPrices($prices, 'machine'));
        }

        return redirect()->route('pricing-settings.edit')->with('status', 'Pricing settings updated.');
    }

    private function nullablePrices(array $prices): array
    {
        return collect($prices)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function modelPrices(array $prices, string $scope): array
    {
        $allowed = match ($scope) {
            'client' => ['mono_ppc', 'colour_ppc', 'included_mono_pages', 'included_colour_pages'],
            default => ['mono_ppc_override', 'colour_ppc_override', 'included_mono_pages_override', 'included_colour_pages_override'],
        };

        return $this->nullablePrices(collect($prices)->only($allowed)->all());
    }

}
