<?php

namespace App\Http\Controllers;

use App\Models\EngineerSkillProfile;
use App\Models\Machine;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceTicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $this->ticketIndexQuery($request)
            ->with(['client', 'site', 'machine', 'assignedEngineer', 'engineerOffers.engineer'])
            ->latest()
            ->paginate(20);

        return view('service-tickets.index', [
            'tickets' => $tickets,
            'areas' => $this->engineerAreas($request),
            'selectedArea' => $request->string('area')->toString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('service-tickets.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->isEngineer(), 403);

        $data = $request->validate([
            'machine_id' => ['required', 'exists:machines,id'],
            'assigned_engineer_id' => ['nullable', 'exists:users,id'],
            'engineer_ids' => ['nullable', 'array'],
            'engineer_ids.*' => ['integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'issue_type' => ['required', 'in:repair,maintenance,install,other'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'description' => ['required', 'string'],
            'required_networking_level' => ['nullable', 'in:none,basic,advanced'],
            'required_vlan_level' => ['nullable', 'in:none,basic,advanced'],
            'required_dhcp_static_ip_level' => ['nullable', 'in:none,basic,advanced'],
            'required_dns_level' => ['nullable', 'in:none,basic,advanced'],
            'required_routing_level' => ['nullable', 'in:none,basic,advanced'],
            'required_firewall_level' => ['nullable', 'in:none,basic,advanced'],
            'requested_for' => ['nullable', 'date'],
        ]);

        $machine = Machine::with(['client', 'site', 'machineModel'])->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->findOrFail($data['machine_id']);
        $requirements = $this->ticketSkillRequirements($data, $machine);

        if (! blank($data['assigned_engineer_id'] ?? null)) {
            $engineer = User::where('role', User::ROLE_ENGINEER)->findOrFail($data['assigned_engineer_id']);
            abort_unless($engineer->engineerCompanies()->whereKey($machine->client->company_id)->exists(), 403);
        }

        $engineerIds = collect($data['engineer_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $eligibleEngineerIds = $this->eligibleEngineerIds($machine, $requirements);
        $engineerIds = filled($data['assigned_engineer_id'] ?? null)
            ? collect()
            : ($engineerIds->isNotEmpty()
                ? $engineerIds->intersect($eligibleEngineerIds)->values()
                : $eligibleEngineerIds->values());

        $ticket = ServiceTicket::create([
            'company_id' => $machine->client->company_id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $request->user()->id,
            'assigned_engineer_id' => $data['assigned_engineer_id'] ?? null,
            'ticket_number' => $this->ticketNumber(),
            'title' => $data['title'],
            'issue_type' => $data['issue_type'],
            'priority' => $data['priority'],
            'description' => $data['description'],
            'required_networking_level' => $requirements['required_networking_level'],
            'required_vlan_level' => $requirements['required_vlan_level'],
            'required_dhcp_static_ip_level' => $requirements['required_dhcp_static_ip_level'],
            'required_dns_level' => $requirements['required_dns_level'],
            'required_routing_level' => $requirements['required_routing_level'],
            'required_firewall_level' => $requirements['required_firewall_level'],
            'requested_for' => $data['requested_for'] ?? null,
        ]);

        $ticket->updates()->create([
            'user_id' => $request->user()->id,
            'status' => ServiceTicket::STATUS_OPEN,
            'notes' => $engineerIds->isNotEmpty()
                ? 'Ticket opened and offered to '.$engineerIds->count().' engineer'.($engineerIds->count() === 1 ? '.' : 's.')
                : 'Ticket opened.',
        ]);

        foreach ($engineerIds as $engineerId) {
            $ticket->engineerOffers()->create(['user_id' => $engineerId]);
        }

        return redirect()->route('service-tickets.show', $ticket)->with('status', 'Service ticket created.');
    }

    public function show(ServiceTicket $serviceTicket, Request $request): View
    {
        $this->authorizeTicket($serviceTicket, $request);
        $canUpdateTicket = $this->canUpdateTicket($serviceTicket, $request);

        return view('service-tickets.show', [
            'ticket' => $serviceTicket->load(['company', 'client', 'site', 'machine.credentials', 'openedBy', 'assignedEngineer', 'engineerOffers.engineer', 'updates.user', 'updates.photos', 'timeLogs.engineer']),
            'machineTicketTimeline' => $this->machineTicketTimeline($serviceTicket, $request, $canUpdateTicket),
            'engineers' => $this->engineersForCompany($serviceTicket->company_id),
            'credentialsUnlocked' => (bool) session('unlocked_ticket_credentials.'.$request->user()->id.'.'.$serviceTicket->id),
            'canAccept' => $this->canEngineerAccept($serviceTicket, $request),
            'canUpdateTicket' => $canUpdateTicket,
        ]);
    }

    public function update(ServiceTicket $serviceTicket, Request $request): RedirectResponse
    {
        $this->authorizeTicket($serviceTicket, $request);
        $allowedStatuses = $request->user()->isEngineer()
            ? [ServiceTicket::STATUS_OPEN, ServiceTicket::STATUS_SCHEDULED, ServiceTicket::STATUS_IN_PROGRESS]
            : [ServiceTicket::STATUS_OPEN, ServiceTicket::STATUS_SCHEDULED, ServiceTicket::STATUS_IN_PROGRESS, ServiceTicket::STATUS_RESOLVED, ServiceTicket::STATUS_CLOSED];

        $data = $request->validate([
            'assigned_engineer_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in($allowedStatuses)],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->user()->isEngineer()) {
            unset($data['assigned_engineer_id']);
            abort_unless($serviceTicket->assigned_engineer_id === $request->user()->id, 403);
        } elseif (! blank($data['assigned_engineer_id'] ?? null)) {
            $engineer = User::where('role', User::ROLE_ENGINEER)->findOrFail($data['assigned_engineer_id']);
            abort_unless($engineer->engineerCompanies()->whereKey($serviceTicket->company_id)->exists(), 403);
        }

        $serviceTicket->update([
            'assigned_engineer_id' => $data['assigned_engineer_id'] ?? $serviceTicket->assigned_engineer_id,
            'status' => $data['status'],
            'scheduled_for' => $data['scheduled_for'] ?? $serviceTicket->scheduled_for,
            'resolution' => $data['resolution'] ?? $serviceTicket->resolution,
            'resolved_at' => $data['status'] === ServiceTicket::STATUS_RESOLVED ? now() : $serviceTicket->resolved_at,
        ]);

        $update = $serviceTicket->updates()->create([
            'user_id' => $request->user()->id,
            'status' => $data['status'],
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'notes' => $data['notes'] ?? null,
            'resolution' => $data['resolution'] ?? null,
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $update->photos()->create([
                'path' => $photo->store('service-ticket-photos', 'public'),
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
            ]);
        }

        return redirect()->route('service-tickets.show', $serviceTicket)->with('status', 'Ticket updated.');
    }

    private function formData(Request $request): array
    {
        $machines = Machine::with(['client', 'site'])->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))->orderBy('machine_name')->get();
        $companyId = Tenant::companyId($request->user());

        return [
            'machines' => $machines,
            'engineers' => $companyId ? $this->engineersForCompany($companyId) : User::where('role', User::ROLE_ENGINEER)->orderBy('name')->get(),
            'skillLevels' => EngineerSkillProfile::LEVELS,
        ];
    }

    private function engineersForCompany(int $companyId)
    {
        return User::where('role', User::ROLE_ENGINEER)
            ->with(['engineerSkillProfile', 'supportedManufacturers'])
            ->whereHas('engineerCompanies', fn ($query) => $query->whereKey($companyId))
            ->orderBy('name')
            ->get();
    }

    private function authorizeTicket(ServiceTicket $ticket, Request $request): void
    {
        if ($request->user()->isEngineer()) {
            abort_unless(
                Tenant::activeCompanyId($request->user()) === $ticket->company_id
                && (
                    $ticket->assigned_engineer_id === $request->user()->id
                    || $ticket->engineerOffers()
                        ->where('user_id', $request->user()->id)
                        ->whereNull('withdrawn_at')
                        ->whereNull('declined_at')
                        ->exists()
                ),
                403,
            );

            return;
        }

        abort_unless(
            $request->user()->isPlatformAdmin()
            || $request->user()->company_id === $ticket->company_id,
            403,
        );
    }

    private function ticketIndexQuery(Request $request)
    {
        $query = Tenant::scope(ServiceTicket::query(), $request->user());

        if ($request->user()->isEngineer()) {
            $query->where(function ($query) use ($request) {
                $query->where('assigned_engineer_id', $request->user()->id)
                    ->orWhere(function ($query) use ($request) {
                        $query->whereNull('assigned_engineer_id')
                            ->whereHas('engineerOffers', fn ($offerQuery) => $offerQuery
                                ->where('user_id', $request->user()->id)
                                ->whereNull('withdrawn_at')
                            ->whereNull('declined_at'));
                    });
            });

            if ($request->filled('area')) {
                $query->whereHas('site', fn ($siteQuery) => $siteQuery->where('city', $request->string('area')->toString()));
            }
        }

        return $query;
    }

    private function engineerAreas(Request $request)
    {
        if (! $request->user()->isEngineer()) {
            return collect();
        }

        return ServiceTicket::query()
            ->where('company_id', Tenant::activeCompanyId($request->user()))
            ->whereNull('assigned_engineer_id')
            ->whereHas('engineerOffers', fn ($offerQuery) => $offerQuery
                ->where('user_id', $request->user()->id)
                ->whereNull('withdrawn_at')
                ->whereNull('declined_at'))
            ->join('sites', 'sites.id', '=', 'service_tickets.site_id')
            ->whereNotNull('sites.city')
            ->where('sites.city', '!=', '')
            ->orderBy('sites.city')
            ->distinct()
            ->pluck('sites.city');
    }

    private function canEngineerAccept(ServiceTicket $ticket, Request $request): bool
    {
        return $request->user()->isEngineer()
            && blank($ticket->assigned_engineer_id)
            && $ticket->engineerOffers()
                ->where('user_id', $request->user()->id)
                ->whereNull('withdrawn_at')
                ->whereNull('declined_at')
                ->exists();
    }

    private function canUpdateTicket(ServiceTicket $ticket, Request $request): bool
    {
        return ! $request->user()->isEngineer()
            || $ticket->assigned_engineer_id === $request->user()->id;
    }

    private function machineTicketTimeline(ServiceTicket $ticket, Request $request, bool $canUpdateTicket)
    {
        if ($request->user()->isEngineer() && ! $canUpdateTicket) {
            return collect();
        }

        return ServiceTicket::query()
            ->with(['openedBy', 'assignedEngineer', 'updates.user', 'updates.photos', 'timeLogs.engineer'])
            ->where('company_id', $ticket->company_id)
            ->where('machine_id', $ticket->machine_id)
            ->latest()
            ->get();
    }

    private function ticketSkillRequirements(array $data, Machine $machine): array
    {
        return [
            'required_networking_level' => $data['required_networking_level'] ?? EngineerSkillProfile::LEVEL_NONE,
            'required_vlan_level' => $data['required_vlan_level'] ?? EngineerSkillProfile::LEVEL_NONE,
            'required_dhcp_static_ip_level' => $data['required_dhcp_static_ip_level'] ?? EngineerSkillProfile::LEVEL_NONE,
            'required_dns_level' => $data['required_dns_level'] ?? EngineerSkillProfile::LEVEL_NONE,
            'required_routing_level' => $data['required_routing_level'] ?? EngineerSkillProfile::LEVEL_NONE,
            'required_firewall_level' => $data['required_firewall_level'] ?? EngineerSkillProfile::LEVEL_NONE,
        ];
    }

    private function eligibleEngineerIds(Machine $machine, array $requirements)
    {
        $manufacturerId = $machine->machineModel?->manufacturer_id;

        return $this->engineersForCompany($machine->client->company_id)
            ->filter(function (User $engineer) use ($manufacturerId, $requirements) {
                if (! $manufacturerId || ! $engineer->supportedManufacturers->contains('id', $manufacturerId)) {
                    return false;
                }

                $profile = $engineer->engineerSkillProfile;

                foreach ($requirements as $field => $requiredLevel) {
                    $profileField = str($field)->after('required_')->toString();

                    if (! EngineerSkillProfile::meets($profile?->{$profileField}, $requiredLevel)) {
                        return false;
                    }
                }

                return true;
            })
            ->pluck('id');
    }

    private function ticketNumber(): string
    {
        do {
            $number = 'ST-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (ServiceTicket::where('ticket_number', $number)->exists());

        return $number;
    }
}
