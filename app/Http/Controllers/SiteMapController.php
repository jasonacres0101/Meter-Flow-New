<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sites = Site::query()
            ->with(['client', 'machines.machineModel'])
            ->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get();

        return view('sites.map', [
            'sites' => $sites,
            'mapSites' => $sites->map(fn (Site $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'client' => $site->client->name,
                'address' => collect([$site->address_line_1, $site->city, $site->postcode])->filter()->join(', '),
                'latitude' => (float) $site->latitude,
                'longitude' => (float) $site->longitude,
                'machines_count' => $site->machines->count(),
                'machines' => $site->machines->map(fn ($machine) => [
                    'id' => $machine->id,
                    'name' => $machine->machine_name,
                    'serial' => $machine->serial_number,
                    'model' => $machine->model,
                    'status' => $machine->is_active ? 'Active' : 'Inactive',
                    'url' => route('machines.show', $machine),
                ])->values(),
                'url' => route('sites.show', $site),
                'client_url' => route('clients.show', $site->client),
            ])->values(),
        ]);
    }
}
