<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\MeterReading;
use App\Models\ServiceAgreement;
use App\Models\Site;
use App\Models\User;
use App\Services\PricingService;
use App\Services\Reports\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_inherits_from_client_then_site_then_machine(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine([
            'client' => ['mono_ppc' => 1.00, 'colour_ppc' => 5.00, 'included_mono_pages' => 100, 'included_colour_pages' => 20],
        ]);

        $pricing = app(PricingService::class);
        $this->assertSame(1.0, $pricing->ratesForMachine($machine)['mono_ppc']);
        $this->assertSame(5.0, $pricing->ratesForMachine($machine)['colour_ppc']);
        $this->assertSame(100.0, $pricing->ratesForMachine($machine)['included_mono_pages']);
        $this->assertSame(20.0, $pricing->ratesForMachine($machine)['included_colour_pages']);

        $site->update(['mono_ppc_override' => 0.80, 'colour_ppc_override' => 4.50, 'included_mono_pages_override' => 80, 'included_colour_pages_override' => 10]);
        $this->assertSame(0.8, $pricing->ratesForMachine($machine->fresh())['mono_ppc']);
        $this->assertSame(80.0, $pricing->ratesForMachine($machine->fresh())['included_mono_pages']);

        $machine->update(['mono_ppc_override' => 0.65, 'colour_ppc_override' => 4.20, 'included_mono_pages_override' => 40, 'included_colour_pages_override' => 5]);
        $this->assertSame(0.65, $pricing->ratesForMachine($machine->fresh())['mono_ppc']);
        $this->assertSame(40.0, $pricing->ratesForMachine($machine->fresh())['included_mono_pages']);
    }

    public function test_revenue_summary_uses_daily_usage_differences_and_effective_ppc(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine([
            'client' => ['mono_ppc' => 1.00, 'colour_ppc' => 5.00, 'included_mono_pages' => 100, 'included_colour_pages' => 20],
        ]);

        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-05-10 08:00:00'),
            'total_counter' => 1000,
            'mono_counter' => 800,
            'colour_counter' => 200,
        ]);

        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-05-11 08:00:00'),
            'total_counter' => 1250,
            'mono_counter' => 980,
            'colour_counter' => 270,
        ]);

        $summary = app(ReportingService::class)->revenueSummary(
            Carbon::parse('2026-05-11'),
            Carbon::parse('2026-05-11'),
            $company->id,
        );

        $this->assertSame(180, $summary['total_mono_pages']);
        $this->assertSame(70, $summary['total_colour_pages']);
        $this->assertSame(250, $summary['total_pages']);
        $this->assertSame(100, $summary['included_mono_pages']);
        $this->assertSame(20, $summary['included_colour_pages']);
        $this->assertSame(80, $summary['chargeable_mono_pages']);
        $this->assertSame(50, $summary['chargeable_colour_pages']);
        $this->assertSame(0.80, $summary['mono_revenue']);
        $this->assertSame(2.50, $summary['colour_revenue']);
        $this->assertSame(3.30, $summary['total_revenue']);
        $this->assertSame($client->name, $summary['by_client']->first()['name']);
        $this->assertSame($site->name, $summary['by_site']->first()['name']);
        $this->assertSame($machine->machine_name, $summary['by_machine']->first()['name']);
    }

    public function test_reports_use_service_agreement_active_on_reading_date(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine([
            'client' => ['mono_ppc' => 1.00, 'colour_ppc' => 5.00, 'included_mono_pages' => 0, 'included_colour_pages' => 0],
        ]);

        ServiceAgreement::create([
            'company_id' => $company->id,
            'agreement_number' => 'SA-OLD',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'mono_ppc' => 1.00,
            'colour_ppc' => 5.00,
            'included_mono_pages' => 0,
            'included_colour_pages' => 0,
        ])->machines()->attach($machine);
        ServiceAgreement::create([
            'company_id' => $company->id,
            'agreement_number' => 'SA-NEW',
            'starts_on' => '2026-05-01',
            'mono_ppc' => 2.00,
            'colour_ppc' => 10.00,
            'included_mono_pages' => 0,
            'included_colour_pages' => 0,
        ])->machines()->attach($machine);

        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-04-29 08:00:00'),
            'total_counter' => 1000,
            'mono_counter' => 800,
            'colour_counter' => 200,
        ]);
        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-04-30 08:00:00'),
            'total_counter' => 1100,
            'mono_counter' => 880,
            'colour_counter' => 220,
        ]);
        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-05-01 08:00:00'),
            'total_counter' => 1200,
            'mono_counter' => 960,
            'colour_counter' => 240,
        ]);

        $april = app(ReportingService::class)->revenueSummary(Carbon::parse('2026-04-30'), Carbon::parse('2026-04-30'), $company->id);
        $may = app(ReportingService::class)->revenueSummary(Carbon::parse('2026-05-01'), Carbon::parse('2026-05-01'), $company->id);

        $this->assertSame(1.80, $april['total_revenue']);
        $this->assertSame('SA-OLD', $april['rows']->first()['service_agreement_number']);
        $this->assertSame(3.60, $may['total_revenue']);
        $this->assertSame('SA-NEW', $may['rows']->first()['service_agreement_number']);
    }

    public function test_company_admin_can_update_pricing_settings(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->put(route('pricing-settings.update'), [
            'clients' => [
                $client->id => ['mono_ppc' => '1.250', 'colour_ppc' => '6.750', 'included_mono_pages' => '1000', 'included_colour_pages' => '250'],
            ],
            'sites' => [
                $site->id => ['mono_ppc_override' => '1.000', 'colour_ppc_override' => '', 'included_mono_pages_override' => '500', 'included_colour_pages_override' => ''],
            ],
            'machines' => [
                $machine->id => ['mono_ppc_override' => '', 'colour_ppc_override' => '5.950', 'included_mono_pages_override' => '', 'included_colour_pages_override' => '75'],
            ],
        ])->assertRedirect(route('pricing-settings.edit'));

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'mono_ppc' => '1.250', 'colour_ppc' => '6.750', 'included_mono_pages' => 1000, 'included_colour_pages' => 250]);
        $this->assertDatabaseHas('sites', ['id' => $site->id, 'mono_ppc_override' => '1.000', 'colour_ppc_override' => null, 'included_mono_pages_override' => 500, 'included_colour_pages_override' => null]);
        $this->assertDatabaseHas('machines', ['id' => $machine->id, 'mono_ppc_override' => null, 'colour_ppc_override' => '5.950', 'included_mono_pages_override' => null, 'included_colour_pages_override' => 75]);
        $this->assertDatabaseCount('service_agreements', 0);
    }

    public function test_company_user_cannot_manage_pricing_settings(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMPANY_USER]);

        $this->actingAs($user)->get(route('pricing-settings.edit'))->assertForbidden();
    }

    public function test_company_admin_can_create_service_agreement_and_attach_multiple_machines(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine();
        $secondMachine = Machine::factory()
            ->for($client)
            ->for($site)
            ->for(MachineModel::factory()->for($company)->create())
            ->create(['serial_number' => 'SECOND-123']);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('service-agreements.store'), [
            'agreement_number' => 'SA-MULTI-001',
            'starts_on' => '2026-05-01',
            'ends_on' => '',
            'mono_ppc' => '0.850',
            'colour_ppc' => '4.950',
            'included_mono_pages' => '1000',
            'included_colour_pages' => '250',
            'is_active' => '1',
            'machine_ids' => [$machine->id, $secondMachine->id],
        ])->assertRedirect();

        $agreement = ServiceAgreement::where('agreement_number', 'SA-MULTI-001')->firstOrFail();

        $this->assertSame($company->id, $agreement->company_id);
        $this->assertNull($agreement->client_id);
        $this->assertNull($agreement->site_id);
        $this->assertNull($agreement->machine_id);
        $this->assertDatabaseHas('machine_service_agreement', ['machine_id' => $machine->id, 'service_agreement_id' => $agreement->id]);
        $this->assertDatabaseHas('machine_service_agreement', ['machine_id' => $secondMachine->id, 'service_agreement_id' => $agreement->id]);
        $this->assertSame('SA-MULTI-001', app(PricingService::class)->ratesForMachine($machine->fresh(), '2026-05-11')['service_agreement_number']);
    }

    public function test_company_admin_can_view_service_agreement_pages(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $agreement = ServiceAgreement::create([
            'company_id' => $company->id,
            'agreement_number' => 'SA-VIEW-001',
            'starts_on' => '2026-05-01',
            'mono_ppc' => 0.85,
            'colour_ppc' => 4.95,
            'included_mono_pages' => 1000,
            'included_colour_pages' => 250,
            'is_active' => true,
        ]);
        $agreement->machines()->attach($machine);

        $this->actingAs($admin)->get(route('service-agreements.index'))->assertOk()->assertSee('Service Agreements');
        $this->actingAs($admin)->get(route('service-agreements.create'))->assertOk()->assertSee('Add Service Agreement');
        $this->actingAs($admin)->get(route('service-agreements.show', $agreement))->assertOk()->assertSee('SA-VIEW-001');
        $this->actingAs($admin)->get(route('service-agreements.edit', $agreement))->assertOk()->assertSee('Edit SA-VIEW-001');
    }

    public function test_revenue_report_can_be_generated_for_a_client_scope(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine([
            'client' => ['mono_ppc' => 1.00, 'colour_ppc' => 5.00],
        ]);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $this->createRevenueReadings($company, $machine);

        $this->actingAs($admin)->get(route('reports.revenue', [
            'period' => 'custom',
            'from' => '2026-05-11',
            'to' => '2026-05-11',
            'scope' => 'client',
            'scope_id' => $client->id,
        ]))->assertOk()
            ->assertSee('Detailed PPC Revenue Reports')
            ->assertSee('Client: '.$client->name)
            ->assertSee('£5.30');
    }

    public function test_revenue_report_exports_csv_excel_and_pdf(): void
    {
        [$company, $client, $site, $machine] = $this->pricedMachine([
            'client' => ['mono_ppc' => 1.00, 'colour_ppc' => 5.00],
        ]);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $this->createRevenueReadings($company, $machine);
        $query = [
            'period' => 'custom',
            'from' => '2026-05-11',
            'to' => '2026-05-11',
            'scope' => 'client',
            'scope_id' => $client->id,
        ];

        $this->actingAs($admin)->get(route('reports.revenue.export', ['format' => 'csv'] + $query))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Copier Revenue Report');

        $this->actingAs($admin)->get(route('reports.revenue.export', ['format' => 'excel'] + $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('<Workbook', false);

        $this->actingAs($admin)->get(route('reports.revenue.export', ['format' => 'pdf'] + $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function pricedMachine(array $overrides = []): array
    {
        $company = Company::factory()->create();
        $client = Client::factory()->for($company)->create($overrides['client'] ?? ['mono_ppc' => 0.85, 'colour_ppc' => 4.95]);
        $site = Site::factory()->for($client)->create($overrides['site'] ?? []);
        $model = MachineModel::factory()->for($company)->create();
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create($overrides['machine'] ?? []);

        return [$company, $client, $site, $machine];
    }

    private function createRevenueReadings(Company $company, Machine $machine): void
    {
        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-05-10 08:00:00'),
            'total_counter' => 1000,
            'mono_counter' => 800,
            'colour_counter' => 200,
        ]);

        MeterReading::factory()->for($machine)->create([
            'company_id' => $company->id,
            'reading_date' => Carbon::parse('2026-05-11 08:00:00'),
            'total_counter' => 1250,
            'mono_counter' => 980,
            'colour_counter' => 270,
        ]);
    }
}
