<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ServiceTicket;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceTicketCompletionController extends Controller
{
    private const REVIEW_FIELDS = [
        'machine_name' => 'Machine name',
        'location' => 'Location',
        'ip_address' => 'IP address',
        'hostname' => 'Hostname',
        'mac_address' => 'MAC address',
        'subnet_mask' => 'Subnet mask',
        'gateway' => 'Gateway',
        'primary_dns' => 'Primary DNS',
        'secondary_dns' => 'Secondary DNS',
        'network_vlan' => 'VLAN',
        'snmp_version' => 'SNMP version',
        'snmp_community' => 'SNMP community',
        'dhcp_enabled' => 'DHCP / static',
        'expected_report_sender_email' => 'Expected report sender',
        'network_notes' => 'Network notes',
    ];

    public function edit(ServiceTicket $serviceTicket, Request $request): View
    {
        $this->authorizeCompletion($serviceTicket, $request);

        return view('service-tickets.complete', [
            'ticket' => $serviceTicket->load(['client', 'site', 'machine.machineModel']),
            'reviewFields' => self::REVIEW_FIELDS,
            'functionalChecks' => $this->functionalChecks(),
        ]);
    }

    public function update(ServiceTicket $serviceTicket, Request $request): RedirectResponse
    {
        $this->authorizeCompletion($serviceTicket, $request);
        $machine = $serviceTicket->machine()->firstOrFail();

        $rules = [
            'machine.machine_name' => ['nullable', 'string', 'max:255'],
            'machine.location' => ['nullable', 'string', 'max:255'],
            'machine.ip_address' => ['nullable', 'ip'],
            'machine.hostname' => ['nullable', 'string', 'max:255'],
            'machine.mac_address' => ['nullable', 'string', 'max:32'],
            'machine.subnet_mask' => ['nullable', 'ip'],
            'machine.gateway' => ['nullable', 'ip'],
            'machine.primary_dns' => ['nullable', 'ip'],
            'machine.secondary_dns' => ['nullable', 'ip'],
            'machine.network_vlan' => ['nullable', 'string', 'max:50'],
            'machine.snmp_version' => ['nullable', 'string', 'max:50'],
            'machine.snmp_community' => ['nullable', 'string', 'max:255'],
            'machine.expected_report_sender_email' => ['nullable', 'email', 'max:255'],
            'machine.network_notes' => ['nullable', 'string'],
            'machine.dhcp_enabled' => ['nullable', 'boolean'],
            'verified_fields' => ['required', 'array'],
            'functional_checks' => ['required', 'array'],
            'resolution' => ['required', 'string'],
        ];

        $attributes = [
            'resolution' => 'resolution notes',
        ];

        foreach (self::REVIEW_FIELDS as $field => $label) {
            $rules["verified_fields.{$field}"] = ['accepted'];
            $attributes["verified_fields.{$field}"] = "{$label} confirmation";
        }

        foreach ($this->functionalChecks() as $field => $label) {
            $rules["functional_checks.{$field}"] = ['accepted'];
            $attributes["functional_checks.{$field}"] = $label;
        }

        $data = $request->validate($rules, [], $attributes);

        $machineData = $data['machine'];
        $machineData['dhcp_enabled'] = $request->boolean('machine.dhcp_enabled');
        $machine->update($machineData);

        $serviceTicket->completionReviews()->create([
            'user_id' => $request->user()->id,
            'machine_snapshot' => $this->machineSnapshot($machine->refresh(), $serviceTicket),
            'verified_fields' => collect(self::REVIEW_FIELDS)->keys()->mapWithKeys(fn ($field) => [$field => true])->all(),
            'functional_checks' => collect($this->functionalChecks())->keys()->mapWithKeys(fn ($field) => [$field => true])->all(),
            'resolution' => $data['resolution'],
        ]);

        $serviceTicket->update([
            'status' => ServiceTicket::STATUS_RESOLVED,
            'resolution' => $data['resolution'],
            'resolved_at' => now(),
        ]);

        $serviceTicket->updates()->create([
            'user_id' => $request->user()->id,
            'status' => ServiceTicket::STATUS_RESOLVED,
            'notes' => 'Engineer completed the machine review and functional checks.',
            'resolution' => $data['resolution'],
        ]);

        return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Job completed and ticket marked resolved.');
    }

    private function authorizeCompletion(ServiceTicket $ticket, Request $request): void
    {
        abort_unless(
            $request->user()->isEngineer()
            && Tenant::activeCompanyId($request->user()) === $ticket->company_id
            && $ticket->assigned_engineer_id === $request->user()->id,
            403,
        );
    }

    private function functionalChecks(): array
    {
        return [
            'machine_working' => 'Machine is working',
            'printing' => 'Printing has been tested',
            'duplex' => 'Double sided printing has been tested',
            'scanning' => 'Scanning has been tested',
            'clean' => 'Machine has been cleaned',
        ];
    }

    private function machineSnapshot(Machine $machine, ServiceTicket $ticket): array
    {
        return [
            'client' => $ticket->client?->name,
            'site' => $ticket->site?->name,
            'manufacturer' => $machine->manufacturer,
            'model' => $machine->model,
            'serial_number' => $machine->serial_number,
            'machine_name' => $machine->machine_name,
            'location' => $machine->location,
            'ip_address' => $machine->ip_address,
            'hostname' => $machine->hostname,
            'mac_address' => $machine->mac_address,
            'subnet_mask' => $machine->subnet_mask,
            'gateway' => $machine->gateway,
            'primary_dns' => $machine->primary_dns,
            'secondary_dns' => $machine->secondary_dns,
            'network_vlan' => $machine->network_vlan,
            'snmp_version' => $machine->snmp_version,
            'snmp_community' => $machine->snmp_community,
            'dhcp_enabled' => $machine->dhcp_enabled,
            'expected_report_sender_email' => $machine->expected_report_sender_email,
            'network_notes' => $machine->network_notes,
        ];
    }
}
