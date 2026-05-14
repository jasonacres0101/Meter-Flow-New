<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\EngineerSkillProfile;
use App\Models\Machine;
use App\Models\MachineCredential;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketCompletionReview;
use App\Models\ServiceTicketTimeLog;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_link_existing_engineer_by_email(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $admin = User::factory()->for($companyB)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $engineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER, 'email' => 'engineer@example.com']);
        $engineer->engineerCompanies()->attach($companyA);

        $this->actingAs($admin)->post(route('users.store'), [
            'company_id' => $companyB->id,
            'name' => 'Existing Engineer',
            'email' => 'engineer@example.com',
            'role' => User::ROLE_ENGINEER,
            'password' => 'password123',
            'is_active' => '1',
        ])->assertRedirect(route('users.show', $engineer));

        $this->assertSame(1, User::where('email', 'engineer@example.com')->count());
        $this->assertTrue($engineer->fresh()->engineerCompanies->contains($companyB));
    }

    public function test_company_admin_can_create_machine_service_ticket(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();

        $this->actingAs($admin)->post(route('service-tickets.store'), [
            'machine_id' => $machine->id,
            'assigned_engineer_id' => $engineer->id,
            'title' => 'Needs maintenance',
            'issue_type' => 'maintenance',
            'priority' => 'high',
            'description' => 'Please inspect rollers and clean the feed path.',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_tickets', [
            'company_id' => $company->id,
            'machine_id' => $machine->id,
            'assigned_engineer_id' => $engineer->id,
            'status' => ServiceTicket::STATUS_OPEN,
        ]);
    }

    public function test_company_admin_can_offer_ticket_to_multiple_engineers_and_first_accepts(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $secondEngineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $secondEngineer->engineerCompanies()->attach($company);
        $this->makeEngineerEligible($secondEngineer, $machine);

        $this->actingAs($admin)->post(route('service-tickets.store'), [
            'machine_id' => $machine->id,
            'engineer_ids' => [$engineer->id, $secondEngineer->id],
            'title' => 'Intermittent fault',
            'issue_type' => 'repair',
            'priority' => 'urgent',
            'description' => 'Device jams every morning.',
        ])->assertRedirect();

        $ticket = ServiceTicket::where('title', 'Intermittent fault')->firstOrFail();

        $this->assertNull($ticket->assigned_engineer_id);
        $this->assertDatabaseHas('service_ticket_engineer_offers', ['service_ticket_id' => $ticket->id, 'user_id' => $engineer->id]);
        $this->assertDatabaseHas('service_ticket_engineer_offers', ['service_ticket_id' => $ticket->id, 'user_id' => $secondEngineer->id]);

        $this->actingAs($engineer)->get(route('service-tickets.index'))->assertOk()->assertSee('Intermittent fault');
        $this->actingAs($secondEngineer)->get(route('service-tickets.index'))->assertOk()->assertSee('Intermittent fault');

        $this->actingAs($engineer)->post(route('service-tickets.accept', $ticket))->assertRedirect(route('service-tickets.show', $ticket));

        $this->assertDatabaseHas('service_tickets', ['id' => $ticket->id, 'assigned_engineer_id' => $engineer->id]);
        $this->assertNotNull($ticket->fresh()->engineerOffers()->where('user_id', $engineer->id)->value('accepted_at'));
        $this->assertNotNull($ticket->fresh()->engineerOffers()->where('user_id', $secondEngineer->id)->value('withdrawn_at'));

        $this->actingAs($secondEngineer)->get(route('service-tickets.index'))->assertOk()->assertDontSee('Intermittent fault');
        $this->actingAs($secondEngineer)->get(route('service-tickets.show', $ticket))->assertForbidden();
    }

    public function test_unoffered_engineer_cannot_see_or_accept_ticket(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $otherEngineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $otherEngineer->engineerCompanies()->attach($company);
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
        ]);
        $ticket->engineerOffers()->create(['user_id' => $engineer->id]);

        $this->actingAs($otherEngineer)->get(route('service-tickets.show', $ticket))->assertForbidden();
        $this->actingAs($otherEngineer)->post(route('service-tickets.accept', $ticket))->assertForbidden();
    }

    public function test_ticket_offers_only_go_to_engineers_matching_manufacturer_and_skill_requirements(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $unsupportedEngineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $unsupportedEngineer->engineerCompanies()->attach($company);
        $wrongManufacturer = Manufacturer::findOrCreateByName('Unsupported Test Make');
        $unsupportedEngineer->engineerSkillProfile()->create([
            'networking_level' => 'advanced',
            'vlan_level' => 'advanced',
            'dhcp_static_ip_level' => 'advanced',
            'dns_level' => 'advanced',
            'routing_level' => 'advanced',
            'firewall_level' => 'advanced',
        ]);
        $unsupportedEngineer->supportedManufacturers()->attach($wrongManufacturer, ['skill_level' => 'advanced']);

        $lowSkillEngineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $lowSkillEngineer->engineerCompanies()->attach($company);
        $this->makeEngineerEligible($lowSkillEngineer, $machine, ['networking_level' => 'basic', 'vlan_level' => 'basic']);

        $this->actingAs($admin)->post(route('service-tickets.store'), [
            'machine_id' => $machine->id,
            'engineer_ids' => [$engineer->id, $unsupportedEngineer->id, $lowSkillEngineer->id],
            'title' => 'Advanced VLAN fault',
            'issue_type' => 'repair',
            'priority' => 'urgent',
            'description' => 'Requires advanced VLAN troubleshooting.',
            'required_networking_level' => 'advanced',
            'required_vlan_level' => 'advanced',
            'required_dhcp_static_ip_level' => 'none',
            'required_dns_level' => 'none',
            'required_routing_level' => 'none',
            'required_firewall_level' => 'none',
        ])->assertRedirect();

        $ticket = ServiceTicket::where('title', 'Advanced VLAN fault')->firstOrFail();

        $this->assertDatabaseHas('service_ticket_engineer_offers', ['service_ticket_id' => $ticket->id, 'user_id' => $engineer->id]);
        $this->assertDatabaseMissing('service_ticket_engineer_offers', ['service_ticket_id' => $ticket->id, 'user_id' => $unsupportedEngineer->id]);
        $this->assertDatabaseMissing('service_ticket_engineer_offers', ['service_ticket_id' => $ticket->id, 'user_id' => $lowSkillEngineer->id]);
        $this->assertDatabaseHas('service_tickets', [
            'id' => $ticket->id,
            'required_networking_level' => 'advanced',
            'required_vlan_level' => 'advanced',
        ]);
    }

    public function test_engineer_dashboard_is_service_focused_and_shows_waiting_ticket_towns(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate(['site' => ['city' => 'Leeds']]);
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'title' => 'Needs engineer acceptance',
            'priority' => 'high',
        ]);
        $ticket->engineerOffers()->create(['user_id' => $engineer->id]);

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Service workload')
            ->assertSee('Waiting acceptance')
            ->assertSee('Leeds')
            ->assertSee($machine->manufacturer)
            ->assertSee($machine->model)
            ->assertSee('Needs engineer acceptance')
            ->assertDontSee('Pages this month')
            ->assertDontSee('Reports today');
    }

    public function test_engineer_ticket_list_filters_by_town_and_hides_full_site_before_acceptance(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate(['site' => ['name' => 'Secret Office', 'city' => 'Leeds']]);
        $secondSite = Site::factory()->for($machine->client)->create(['name' => 'Hidden Branch', 'city' => 'Manchester']);
        $secondMachine = Machine::factory()
            ->for($machine->client)
            ->for($secondSite)
            ->for(MachineModel::factory()->for($company)->create())
            ->create(['machine_name' => 'Manchester Device', 'serial_number' => 'MANC-1']);

        $leedsTicket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'title' => 'Leeds available job',
            'required_networking_level' => 'advanced',
            'required_vlan_level' => 'basic',
        ]);
        $manchesterTicket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $secondMachine->client_id,
            'site_id' => $secondMachine->site_id,
            'machine_id' => $secondMachine->id,
            'opened_by_user_id' => $admin->id,
            'title' => 'Manchester available job',
        ]);
        $leedsTicket->engineerOffers()->create(['user_id' => $engineer->id]);
        $manchesterTicket->engineerOffers()->create(['user_id' => $engineer->id]);

        $this->actingAs($engineer)->get(route('service-tickets.index', ['area' => 'Leeds']))
            ->assertOk()
            ->assertSee('Leeds available job')
            ->assertSee('Town: Leeds')
            ->assertSee($machine->manufacturer)
            ->assertSee($machine->model)
            ->assertSee('Accept ticket to view full machine and site details.')
            ->assertSee('Network: Advanced')
            ->assertSee('VLAN: Basic')
            ->assertDontSee('Manchester available job')
            ->assertDontSee('Secret Office')
            ->assertDontSee($machine->machine_name);

        $this->actingAs($engineer)->get(route('service-tickets.show', $leedsTicket))
            ->assertOk()
            ->assertSee('Required skills')
            ->assertSee('These are shown before acceptance')
            ->assertSee($machine->manufacturer)
            ->assertSee($machine->model)
            ->assertSee('Networking')
            ->assertSee('Advanced')
            ->assertSee('VLANs')
            ->assertSee('Basic')
            ->assertDontSee('Secret Office')
            ->assertDontSee($machine->machine_name);
    }

    public function test_engineer_can_update_ticket_with_date_notes_and_photos(): void
    {
        Storage::fake('public');
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
        ]);

        $this->actingAs($engineer)->put(route('service-tickets.update', $ticket), [
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
            'scheduled_for' => '2026-05-12 10:30',
            'notes' => 'Rollers replaced.',
            'resolution' => 'Machine tested successfully.',
            'photos' => [UploadedFile::fake()->image('repair.jpg')],
        ])->assertRedirect(route('service-tickets.show', $ticket));

        $this->assertDatabaseHas('service_tickets', ['id' => $ticket->id, 'status' => ServiceTicket::STATUS_IN_PROGRESS, 'resolution' => 'Machine tested successfully.']);
        $this->assertDatabaseHas('service_ticket_updates', ['service_ticket_id' => $ticket->id, 'user_id' => $engineer->id, 'status' => ServiceTicket::STATUS_IN_PROGRESS]);
        $this->assertDatabaseCount('service_ticket_photos', 1);
    }

    public function test_engineer_cannot_resolve_or_close_ticket_from_basic_update_form(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($engineer)
            ->from(route('service-tickets.show', $ticket))
            ->put(route('service-tickets.update', $ticket), [
                'status' => ServiceTicket::STATUS_RESOLVED,
                'notes' => 'Trying to resolve without review.',
            ])
            ->assertRedirect(route('service-tickets.show', $ticket))
            ->assertSessionHasErrors('status');

        $this->actingAs($engineer)
            ->from(route('service-tickets.show', $ticket))
            ->put(route('service-tickets.update', $ticket), [
                'status' => ServiceTicket::STATUS_CLOSED,
                'notes' => 'Trying to close from engineer login.',
            ])
            ->assertRedirect(route('service-tickets.show', $ticket))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('service_tickets', [
            'id' => $ticket->id,
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_engineer_completes_review_to_resolve_ticket_and_update_machine_details(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate(['machine' => [
            'serial_number' => 'REVIEW-100',
            'machine_name' => 'Old copier name',
            'location' => 'Old location',
            'ip_address' => '192.168.10.45',
            'hostname' => 'old-host',
            'network_notes' => 'Old notes',
        ]]);
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.complete.edit', $ticket))
            ->assertOk()
            ->assertSee('Complete Job Review')
            ->assertSee('Onsite Details')
            ->assertSee('Final Checks')
            ->assertDontSee('This is correct')
            ->assertSee('Confirm checks and resolve ticket');

        $this->actingAs($engineer)
            ->from(route('service-tickets.complete.edit', $ticket))
            ->put(route('service-tickets.complete.update', $ticket), [
                'machine' => [
                    'machine_name' => 'Updated copier name',
                ],
                'verified_fields' => [],
                'functional_checks' => [],
                'resolution' => '',
            ])
            ->assertRedirect(route('service-tickets.complete.edit', $ticket))
            ->assertSessionHasErrors([
                'verified_fields.machine_name',
                'verified_fields.location',
                'functional_checks.machine_working',
                'functional_checks.printing',
                'resolution',
            ]);

        $payload = [
            'machine' => [
                'machine_name' => 'Updated copier name',
                'location' => 'Print room',
                'ip_address' => '192.168.10.55',
                'hostname' => 'print-room-mfp',
                'mac_address' => '00:11:22:33:44:55',
                'subnet_mask' => '255.255.255.0',
                'gateway' => '192.168.10.1',
                'primary_dns' => '8.8.8.8',
                'secondary_dns' => '1.1.1.1',
                'network_vlan' => 'Printer VLAN',
                'snmp_version' => 'v2c',
                'snmp_community' => 'public',
                'dhcp_enabled' => '1',
                'expected_report_sender_email' => 'reports@example.test',
                'network_notes' => 'Confirmed while onsite.',
            ],
            'verified_fields' => collect([
                'machine_name',
                'location',
                'ip_address',
                'hostname',
                'mac_address',
                'subnet_mask',
                'gateway',
                'primary_dns',
                'secondary_dns',
                'network_vlan',
                'snmp_version',
                'snmp_community',
                'dhcp_enabled',
                'expected_report_sender_email',
                'network_notes',
            ])->mapWithKeys(fn ($field) => [$field => '1'])->all(),
            'functional_checks' => [
                'machine_working' => '1',
                'printing' => '1',
                'duplex' => '1',
                'scanning' => '1',
                'clean' => '1',
            ],
            'resolution' => 'Completed service, tested functions and returned machine to customer.',
        ];

        $this->actingAs($engineer)->put(route('service-tickets.complete.update', $ticket), $payload)
            ->assertRedirect(route('service-tickets.show', $ticket));

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'machine_name' => 'Updated copier name',
            'location' => 'Print room',
            'ip_address' => '192.168.10.55',
            'hostname' => 'print-room-mfp',
        ]);
        $this->assertDatabaseHas('service_tickets', [
            'id' => $ticket->id,
            'status' => ServiceTicket::STATUS_RESOLVED,
            'resolution' => 'Completed service, tested functions and returned machine to customer.',
        ]);
        $this->assertDatabaseHas('service_ticket_completion_reviews', [
            'service_ticket_id' => $ticket->id,
            'user_id' => $engineer->id,
        ]);
        $this->assertTrue(ServiceTicketCompletionReview::where('service_ticket_id', $ticket->id)->firstOrFail()->functional_checks['duplex']);
    }

    public function test_engineer_can_manually_start_and_stop_job_timer(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
        ]);

        Carbon::setTestNow('2026-05-13 09:00:00');

        $this->actingAs($engineer)->post(route('service-tickets.timer.start', $ticket))
            ->assertRedirect(route('service-tickets.show', $ticket));

        $this->assertDatabaseHas('service_ticket_time_logs', [
            'service_ticket_id' => $ticket->id,
            'user_id' => $engineer->id,
            'stopped_at' => null,
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Job Timer')
            ->assertSee('Manual time recording for this ticket. You control when it starts and stops.')
            ->assertSee('Running')
            ->assertSee('Stop job timer')
            ->assertSee('What did you do during this work session?');

        Carbon::setTestNow('2026-05-13 10:15:00');

        $this->actingAs($engineer)
            ->from(route('service-tickets.show', $ticket))
            ->post(route('service-tickets.timer.stop', $ticket), [
                'notes' => '',
            ])
            ->assertRedirect(route('service-tickets.show', $ticket))
            ->assertSessionHasErrors('notes');

        $this->actingAs($engineer)->post(route('service-tickets.timer.stop', $ticket), [
            'notes' => 'Rebuilt fuser and ran test prints.',
        ])->assertRedirect(route('service-tickets.show', $ticket));

        $timeLog = ServiceTicketTimeLog::where('service_ticket_id', $ticket->id)->firstOrFail();

        $this->assertSame(4500, $timeLog->duration_seconds);
        $this->assertSame('Rebuilt fuser and ran test prints.', $timeLog->notes);
        $this->assertNotNull($timeLog->stopped_at);
        $this->assertDatabaseHas('service_ticket_updates', [
            'service_ticket_id' => $ticket->id,
            'user_id' => $engineer->id,
            'notes' => "Job timer stopped. Time logged: 1h 15m.\n\nRebuilt fuser and ran test prints.",
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('time log note')
            ->assertSee('Rebuilt fuser and ran test prints.');

        Carbon::setTestNow();
    }

    public function test_engineer_cannot_start_timer_on_unaccepted_ticket(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => null,
        ]);
        $ticket->engineerOffers()->create(['user_id' => $engineer->id]);

        $this->actingAs($engineer)->post(route('service-tickets.timer.start', $ticket))->assertForbidden();
        $this->assertDatabaseCount('service_ticket_time_logs', 0);
    }

    public function test_assigned_engineer_can_open_machine_web_panel_from_ticket(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate(['machine' => [
            'ip_address' => '192.168.10.45',
            'hostname' => 'service-mfp',
            'mac_address' => '00:11:22:33:44:55',
            'subnet_mask' => '255.255.255.0',
            'gateway' => '192.168.10.1',
            'primary_dns' => '8.8.8.8',
            'secondary_dns' => '1.1.1.1',
            'network_vlan' => 'Printers',
            'snmp_version' => 'v2c',
            'snmp_community' => 'public',
            'dhcp_enabled' => false,
            'network_notes' => 'Use the service VLAN before opening the web panel.',
        ]]);
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Open device web panel')
            ->assertSee('Network Settings')
            ->assertSee('service-mfp')
            ->assertSee('00:11:22:33:44:55')
            ->assertSee('192.168.10.1')
            ->assertSee('Printers')
            ->assertSee('SNMP community')
            ->assertSee('public')
            ->assertSee('Use the service VLAN before opening the web panel.')
            ->assertDontSee('value="closed"', false)
            ->assertSee('href="http://192.168.10.45"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('Make sure you are on the same network as this printer/copier before continuing.');
    }

    public function test_assigned_engineer_sees_machine_service_history_on_ticket(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $previousTicket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'title' => 'Previous roller clean',
            'status' => ServiceTicket::STATUS_RESOLVED,
            'resolution' => 'Feed path cleaned.',
            'resolved_at' => now()->subDays(10),
            'created_at' => now()->subDays(12),
        ]);
        $previousTicket->updates()->create([
            'user_id' => $admin->id,
            'status' => ServiceTicket::STATUS_RESOLVED,
            'notes' => 'Initial diagnostic found worn feed roller.',
            'resolution' => 'Feed path cleaned.',
        ]);

        $currentTicket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'title' => 'Current print quality fault',
        ]);
        $currentTicket->updates()->create([
            'user_id' => $engineer->id,
            'status' => ServiceTicket::STATUS_IN_PROGRESS,
            'notes' => 'Checking fuser and transfer belt.',
        ]);

        $otherMachine = Machine::factory()
            ->for($machine->client)
            ->for($machine->site)
            ->for($machine->machineModel)
            ->create(['serial_number' => 'OTHER-HISTORY-1']);
        ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $otherMachine->client_id,
            'site_id' => $otherMachine->site_id,
            'machine_id' => $otherMachine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
            'title' => 'Unrelated machine fault',
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.show', $currentTicket))
            ->assertOk()
            ->assertSee('Machine Service History')
            ->assertSee('All service tickets recorded against this machine.')
            ->assertSee('Previous roller clean')
            ->assertSee('Initial diagnostic found worn feed roller.')
            ->assertSee('Current print quality fault')
            ->assertSee('Checking fuser and transfer belt.')
            ->assertDontSee('Unrelated machine fault');
    }

    public function test_engineer_does_not_see_revenue_menu_or_commercial_machine_details(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();

        $this->actingAs($engineer)->get(route('service-tickets.index'))
            ->assertOk()
            ->assertDontSee('Revenue Reports')
            ->assertDontSee('Pricing')
            ->assertDontSee('Incoming Email Store');

        $this->actingAs($engineer)->get(route('machines.show', $machine))
            ->assertOk()
            ->assertDontSee('B/W revenue')
            ->assertDontSee('Commercial Rate')
            ->assertDontSee('Raw Emails');
    }

    public function test_engineer_cannot_open_revenue_or_parser_settings_routes(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();

        $this->actingAs($engineer)->get(route('reports.revenue'))->assertForbidden();
        $this->actingAs($engineer)->get(route('machine-models.index'))->assertForbidden();
        $this->actingAs($engineer)->get(route('report-templates.index'))->assertForbidden();
        $this->actingAs($engineer)->get(route('incoming-report-emails.index'))->assertForbidden();
    }

    public function test_engineer_can_create_pin_and_reveal_ticket_machine_passwords(): void
    {
        [$company, $admin, $machine, $engineer] = $this->ticketEstate();
        $ticket = ServiceTicket::factory()->create([
            'company_id' => $company->id,
            'client_id' => $machine->client_id,
            'site_id' => $machine->site_id,
            'machine_id' => $machine->id,
            'opened_by_user_id' => $admin->id,
            'assigned_engineer_id' => $engineer->id,
        ]);
        MachineCredential::factory()->for($machine)->create([
            'label' => 'Web admin',
            'username' => 'admin',
            'password' => 'ticket-secret',
        ]);

        $this->actingAs($engineer)->get(route('service-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Machine Password Access')
            ->assertSee('Create 4-8 digit PIN')
            ->assertDontSee('ticket-secret');

        $this->actingAs($engineer)->put(route('engineer-pin.update'), [
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('123456', $engineer->fresh()->engineer_pin));

        $this->actingAs($engineer)->post(route('service-tickets.credential-access.store', $ticket), [
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');

        $this->actingAs($engineer)->post(route('service-tickets.credential-access.store', $ticket), [
            'pin' => '123456',
        ])->assertRedirect();

        $this->actingAs($engineer)->get(route('service-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('ticket-secret')
            ->assertSee('Web admin');
    }

    private function ticketEstate(array $overrides = []): array
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create($overrides['site'] ?? []);
        $model = MachineModel::factory()->for($company)->create();
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create($overrides['machine'] ?? []);
        $engineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $engineer->engineerCompanies()->attach($company);
        $this->makeEngineerEligible($engineer, $machine);

        return [$company, $admin, $machine, $engineer];
    }

    private function makeEngineerEligible(User $engineer, Machine $machine, array $skills = []): void
    {
        $engineer->engineerSkillProfile()->updateOrCreate(
            ['user_id' => $engineer->id],
            array_merge([
                'networking_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'vlan_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dhcp_static_ip_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dns_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'routing_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'firewall_level' => EngineerSkillProfile::LEVEL_ADVANCED,
            ], $skills),
        );

        if ($machine->machineModel?->manufacturer_id) {
            $engineer->supportedManufacturers()->syncWithoutDetaching([
                $machine->machineModel->manufacturer_id => ['skill_level' => EngineerSkillProfile::LEVEL_ADVANCED],
            ]);
        }
    }
}
