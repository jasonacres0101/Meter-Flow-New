<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\EmailSource;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\ParserDefinition;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaasAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_company(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'New Tenant Ltd',
            'account_reference' => 'NTL',
            'company_number' => '12345678',
            'vat_number' => 'GB123456789',
            'billing_email' => 'billing@tenant.test',
            'monthly_machine_rate_override' => '8.75',
            'phone' => '01234 567890',
            'website' => 'https://tenant.test',
            'address_line_1' => '1 Demo Street',
            'address_line_2' => 'Business Park',
            'city' => 'London',
            'county' => 'Greater London',
            'postcode' => 'SW1A 1AA',
            'country' => 'United Kingdom',
            'admin_name' => 'Tenant Admin',
            'admin_email' => 'admin@tenant.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'is_active' => '1',
        ])->assertRedirect(route('companies.index'));

        $this->assertDatabaseHas('companies', [
            'name' => 'New Tenant Ltd',
            'company_number' => '12345678',
            'vat_number' => 'GB123456789',
            'postcode' => 'SW1A 1AA',
            'monthly_machine_rate_override' => 8.75,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@tenant.test',
            'role' => User::ROLE_COMPANY_ADMIN,
        ]);
    }

    public function test_platform_admin_lands_on_accounts_and_cannot_open_customer_operations(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('companies.index'));
        $this->actingAs($admin)->get(route('clients.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('sites.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('sites.map'))->assertForbidden();
        $this->actingAs($admin)->get(route('machines.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('service-tickets.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.revenue'))->assertForbidden();
        $this->actingAs($admin)->get(route('incoming-report-emails.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_platform_admin_can_view_all_users_and_reset_passwords_and_pins(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['name' => 'Supported Tenant']);
        $companyAdmin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN, 'email' => 'tenant-admin@example.com']);
        $engineer = User::factory()->create(['role' => User::ROLE_ENGINEER, 'company_id' => null, 'engineer_pin' => bcrypt('1234')]);
        $engineer->engineerCompanies()->attach($company);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('tenant-admin@example.com')
            ->assertSee('Supported Tenant');

        $this->actingAs($admin)->put(route('users.update', $companyAdmin), [
            'name' => $companyAdmin->name,
            'email' => $companyAdmin->email,
            'company_id' => $company->id,
            'role' => User::ROLE_COMPANY_ADMIN,
            'password' => 'new-password',
            'is_active' => '1',
        ])->assertRedirect(route('users.show', $companyAdmin));

        $this->assertTrue(Hash::check('new-password', $companyAdmin->fresh()->password));

        $this->actingAs($admin)->put(route('users.update', $engineer), [
            'name' => $engineer->name,
            'email' => $engineer->email,
            'company_id' => '',
            'role' => User::ROLE_ENGINEER,
            'engineer_pin' => '9876',
            'engineer_pin_confirmation' => '9876',
            'is_active' => '1',
        ])->assertRedirect(route('users.show', $engineer));

        $this->assertTrue(Hash::check('9876', $engineer->fresh()->engineer_pin));

        $this->actingAs($admin)->put(route('users.update', $engineer), [
            'name' => $engineer->name,
            'email' => $engineer->email,
            'company_id' => '',
            'role' => User::ROLE_ENGINEER,
            'clear_engineer_pin' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('users.show', $engineer));

        $this->assertNull($engineer->fresh()->engineer_pin);
    }

    public function test_platform_admin_can_impersonate_company_user_and_return(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['name' => 'Helped Tenant']);
        $companyAdmin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN, 'name' => 'Helped Admin']);

        $this->actingAs($admin)->post(route('users.impersonate', $companyAdmin))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($companyAdmin);
        $this->assertSame($admin->id, session('impersonator_user_id'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Support mode')
            ->assertSee('Helped Admin')
            ->assertSee('Return to SaaS admin');

        $this->delete(route('impersonation.destroy'))
            ->assertRedirect(route('users.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonator_user_id'));
    }

    public function test_platform_admin_cannot_impersonate_another_platform_admin(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $otherAdmin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->post(route('users.impersonate', $otherAdmin))
            ->assertStatus(422);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_platform_admin_account_detail_shows_support_overview(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create([
            'name' => 'Support View Ltd',
            'billing_email' => 'billing@support.test',
            'monthly_machine_rate_override' => 12.50,
            'company_number' => '87654321',
            'vat_number' => 'GB987654321',
            'website' => 'https://support-view.test',
            'address_line_1' => '10 Support House',
            'city' => 'Manchester',
            'postcode' => 'M1 1AA',
            'country' => 'United Kingdom',
        ]);
        $companyAdmin = User::factory()->for($company)->create([
            'name' => 'Office Admin',
            'role' => User::ROLE_COMPANY_ADMIN,
            'last_login_at' => '2026-05-11 09:15:00',
        ]);
        $engineer = User::factory()->create(['role' => User::ROLE_ENGINEER, 'company_id' => null, 'last_login_at' => '2026-05-10 10:00:00']);
        $engineer->engineerCompanies()->attach($company);
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create();
        $model = MachineModel::factory()->for($company)->create();
        Machine::factory()->for($client)->for($site)->for($model)->create();
        EmailSource::factory()->for($company)->create(['name' => 'Reports Inbox']);

        $this->actingAs($admin)->get(route('companies.show', $company))
            ->assertOk()
            ->assertSee('Support View Ltd')
            ->assertSee('Office users')
            ->assertSee('Sites')
            ->assertSee('Machines')
            ->assertSee('Office Admin')
            ->assertSee('87654321')
            ->assertSee('GB987654321')
            ->assertSee('GBP 12.50')
            ->assertSee('10 Support House')
            ->assertSee('M1 1AA')
            ->assertSee($companyAdmin->email)
            ->assertSee('11 May 2026 09:15')
            ->assertSee($engineer->email)
            ->assertSee('Reports Inbox');
    }

    public function test_platform_admin_only_sees_master_library_records(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create();
        $companyManufacturer = Manufacturer::findOrCreateByName('CompanyOnly');
        $masterManufacturer = Manufacturer::findOrCreateByName('MasterOnly');
        $companyModel = MachineModel::factory()->for($company)->create(['manufacturer_id' => $companyManufacturer->id, 'manufacturer' => $companyManufacturer->name]);
        $masterModel = MachineModel::factory()->create(['company_id' => null, 'manufacturer_id' => $masterManufacturer->id, 'manufacturer' => $masterManufacturer->name]);
        $companyTemplate = ReportTemplate::factory()->for($companyModel, 'machineModel')->create(['company_id' => $company->id, 'template_name' => 'Company template']);
        $masterTemplate = ReportTemplate::factory()->for($masterModel, 'machineModel')->create(['company_id' => null, 'template_name' => 'Master template']);
        $companySource = EmailSource::factory()->for($company)->create(['name' => 'Company mailbox']);
        $masterSource = EmailSource::factory()->create(['company_id' => null, 'name' => 'Master mailbox']);

        $this->actingAs($admin)->get(route('machine-models.index'))
            ->assertOk()
            ->assertSee($masterModel->manufacturer)
            ->assertDontSee($companyModel->manufacturer);

        $this->actingAs($admin)->get(route('report-templates.index'))
            ->assertOk()
            ->assertSee($masterTemplate->template_name)
            ->assertDontSee($companyTemplate->template_name);

        $this->actingAs($admin)->get(route('email-sources.index'))
            ->assertOk()
            ->assertSee($masterSource->name)
            ->assertDontSee($companySource->name);
    }

    public function test_platform_admin_can_filter_and_clone_global_report_templates(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $masterModel = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);
        $masterTemplate = ReportTemplate::factory()->for($masterModel, 'machineModel')->create([
            'company_id' => null,
            'template_name' => 'Sharp MX master template',
            'sample_subject' => 'MX-2630N Status Message',
            'parser_type' => 'sharp_mx_status_email',
            'parser_configuration' => ['serial_number_labels' => ['Serial Number']],
        ]);

        $this->actingAs($admin)->get(route('report-templates.index', ['q' => 'Sharp MX']))
            ->assertOk()
            ->assertSee('Sharp MX master template')
            ->assertSee('Clone');

        $this->actingAs($admin)->post(route('report-templates.duplicate', $masterTemplate))
            ->assertRedirect();

        $this->assertDatabaseHas('report_templates', [
            'company_id' => null,
            'machine_model_id' => $masterModel->id,
            'template_name' => 'Sharp MX-2630N',
            'version' => 2,
            'parser_type' => 'sharp_mx_status_email',
            'approval_status' => ReportTemplate::STATUS_APPROVED_GLOBAL,
        ]);
    }

    public function test_platform_admin_can_approve_tenant_template_as_global_version(): void
    {
        $company = Company::factory()->create();
        $platformAdmin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Xerox');
        $companyModel = MachineModel::factory()->create([
            'company_id' => $company->id,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'VersaLink C405',
            'parser_type' => 'generic_counter_email',
        ]);
        $globalModel = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'VersaLink C405',
            'parser_type' => 'generic_counter_email',
        ]);
        $tenantTemplate = ReportTemplate::factory()->for($companyModel, 'machineModel')->create([
            'company_id' => $company->id,
            'template_name' => 'Xerox VersaLink C405',
            'family_key' => 'xerox_versalink_c405_generic_counter_email',
            'version' => 1,
            'parser_type' => 'generic_counter_email',
            'approval_status' => ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW,
        ]);

        $this->actingAs($platformAdmin)->get(route('report-templates.index', ['owner' => 'pending']))
            ->assertOk()
            ->assertSee('Xerox VersaLink C405 v1')
            ->assertSee('Approve global');

        $this->actingAs($platformAdmin)->post(route('report-templates.approve-global', $tenantTemplate))
            ->assertRedirect();

        $this->assertDatabaseHas('report_templates', [
            'company_id' => null,
            'machine_model_id' => $globalModel->id,
            'template_name' => 'Xerox VersaLink C405',
            'version' => 1,
            'approval_status' => ReportTemplate::STATUS_APPROVED_GLOBAL,
        ]);
    }

    public function test_platform_admin_can_create_parser_profile_for_master_models(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Ricoh');

        $this->actingAs($admin)->post(route('parser-definitions.store'), [
            'name' => 'Ricoh status email',
            'parser_key' => 'ricoh_status_email',
            'engine_type' => ParserDefinition::ENGINE_GENERIC_COUNTER,
            'default_configuration' => '{"serial_number_labels":["Serial No"],"total_counter_labels":["Total Count"]}',
            'is_active' => '1',
        ])->assertRedirect(route('parser-definitions.index'));

        $this->assertDatabaseHas('parser_definitions', [
            'parser_key' => 'ricoh_status_email',
            'engine_type' => ParserDefinition::ENGINE_GENERIC_COUNTER,
        ]);

        $this->actingAs($admin)->post(route('machine-models.store'), [
            'manufacturer_id' => $manufacturer->id,
            'model_name' => 'IM C3000',
            'parser_type' => 'ricoh_status_email',
        ])->assertRedirect();

        $this->assertDatabaseHas('machine_models', [
            'company_id' => null,
            'model_name' => 'IM C3000',
            'parser_type' => 'ricoh_status_email',
        ]);
    }

    public function test_company_admin_can_add_user_to_own_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'company_id' => Company::factory()->create()->id,
            'role' => User::ROLE_COMPANY_USER,
            'password' => 'password123',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'company_id' => $company->id,
            'role' => User::ROLE_COMPANY_USER,
        ]);
    }

    public function test_company_admin_user_form_does_not_offer_platform_company(): void
    {
        $company = Company::factory()->create(['name' => 'Tenant Only Ltd']);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('Tenant Only Ltd')
            ->assertDontSee('<option value="">Platform</option>', false);
    }

    public function test_company_user_cannot_see_another_company_machine(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);
        $otherClient = Client::factory()->for($otherCompany)->create();
        $otherSite = Site::factory()->for($otherClient)->create();
        $otherModel = MachineModel::factory()->for($otherCompany)->create();
        $machine = Machine::factory()->for($otherClient)->for($otherSite)->for($otherModel)->create();

        $this->actingAs($user)->get(route('machines.show', $machine))->assertForbidden();
    }

    public function test_company_user_can_create_clients_and_sites_for_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);

        $this->actingAs($user)->get(route('clients.create'))
            ->assertOk()
            ->assertSee('Add Client');

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Acme Legal',
            'account_reference' => 'ACME-001',
            'contact_email' => 'ops@acme.test',
            'phone' => '01234 567890',
            'is_active' => '1',
            'after_save' => 'add_site',
        ])->assertRedirect();

        $client = Client::where('company_id', $company->id)->where('name', 'Acme Legal')->firstOrFail();

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Acme Accounts',
            'after_save' => 'add_site',
        ])->assertRedirect(route('sites.create', ['client_id' => Client::where('name', 'Acme Accounts')->firstOrFail()->id]));

        $this->actingAs($user)->get(route('sites.create', ['client_id' => $client->id]))
            ->assertOk()
            ->assertSee('Add Site')
            ->assertSee('Acme Legal');

        $this->actingAs($user)->post(route('sites.store'), [
            'client_id' => $client->id,
            'name' => 'Acme London',
            'address_line_1' => '1 Example Street',
            'city' => 'London',
            'postcode' => 'EC1A 1AA',
            'latitude' => '51.5150000',
            'longitude' => '-0.0900000',
            'is_active' => '1',
            'after_save' => 'add_machine',
        ])->assertRedirect();

        $site = Site::where('client_id', $client->id)->where('name', 'Acme London')->firstOrFail();

        $this->assertDatabaseHas('sites', [
            'client_id' => $client->id,
            'name' => 'Acme London',
            'city' => 'London',
        ]);

        $this->actingAs($user)->post(route('sites.store'), [
            'client_id' => $client->id,
            'name' => 'Acme Manchester',
            'after_save' => 'add_machine',
        ])->assertRedirect(route('machines.create', [
            'client_id' => $client->id,
            'site_id' => Site::where('name', 'Acme Manchester')->firstOrFail()->id,
        ]));

        $this->actingAs($user)->get(route('machines.create', ['client_id' => $client->id, 'site_id' => $site->id]))
            ->assertOk()
            ->assertSee('option value="'.$client->id.'" selected', false)
            ->assertSee('option value="'.$site->id.'" selected', false);
    }

    public function test_engineer_cannot_create_clients_or_sites(): void
    {
        $company = Company::factory()->create();
        $engineer = User::factory()->create(['role' => User::ROLE_ENGINEER, 'company_id' => null]);
        $engineer->engineerCompanies()->attach($company);
        $client = Client::factory()->for($company)->create();

        $this->actingAs($engineer)->withSession(['active_company_id' => $company->id])->get(route('clients.create'))->assertForbidden();
        $this->actingAs($engineer)->withSession(['active_company_id' => $company->id])->post(route('sites.store'), [
            'client_id' => $client->id,
            'name' => 'Blocked site',
        ])->assertForbidden();
    }

    public function test_company_user_can_manage_stock_and_move_it_to_site(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);
        $client = Client::factory()->for($company)->create(['name' => 'Stock Client']);
        $site = Site::factory()->for($client)->create(['name' => 'Stock Site']);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $modelA = MachineModel::factory()->for($company)->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-2630N',
        ]);
        $modelB = MachineModel::factory()->for($company)->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-3051',
        ]);

        $this->actingAs($user)->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Stock')
            ->assertSee('Add product');

        $this->actingAs($user)->get(route('stock.create'))
            ->assertOk()
            ->assertSee('Add Stock Product')
            ->assertSee('Compatible Models')
            ->assertSee('Sharp MX-2630N');

        $this->actingAs($user)->post(route('stock.store'), [
            'name' => 'Sharp black toner',
            'type' => StockProduct::TYPE_TONER,
            'supplier' => 'ABC Supplies',
            'machine_model_ids' => [$modelA->id, $modelB->id],
            'quantity' => 5,
        ])->assertRedirect();

        $product = StockProduct::where('company_id', $company->id)->where('name', 'Sharp black toner')->firstOrFail();
        $this->assertTrue($product->machineModels()->whereKey($modelA->id)->exists());
        $this->assertTrue($product->machineModels()->whereKey($modelB->id)->exists());

        $this->assertDatabaseHas('stock_balances', [
            'company_id' => $company->id,
            'stock_product_id' => $product->id,
            'site_id' => null,
            'quantity' => 5,
        ]);

        $this->actingAs($user)->post(route('stock.add'), [
            'stock_product_id' => $product->id,
            'quantity' => 3,
            'notes' => 'Supplier delivery',
        ])->assertRedirect(route('stock.show', $product));

        $this->actingAs($user)->post(route('stock.transfer'), [
            'stock_product_id' => $product->id,
            'site_id' => $site->id,
            'quantity' => 4,
            'notes' => 'Moved for install',
        ])->assertRedirect(route('stock.show', $product));

        $this->assertSame(4, StockBalance::where('stock_product_id', $product->id)->whereNull('site_id')->firstOrFail()->quantity);
        $this->assertSame(4, StockBalance::where('stock_product_id', $product->id)->where('site_id', $site->id)->firstOrFail()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $company->id,
            'stock_product_id' => $product->id,
            'to_site_id' => $site->id,
            'movement_type' => 'transfer_to_site',
            'quantity' => 4,
        ]);

        $this->actingAs($user)->get(route('stock.show', $product))
            ->assertOk()
            ->assertSee('Where This Stock Is Held')
            ->assertSee('Machines Using Compatible Models')
            ->assertSee('Movement History')
            ->assertSee('Stock Client')
            ->assertSee('Stock Site')
            ->assertSee('Sharp MX-2630N')
            ->assertSee('Sharp MX-3051');

        $this->actingAs($user)->get(route('stock.edit', $product))
            ->assertOk()
            ->assertSee('Edit Stock Product');

        $this->actingAs($user)->put(route('stock.update', $product), [
            'name' => 'Sharp black toner cartridge',
            'type' => StockProduct::TYPE_TONER,
            'supplier' => 'ABC Supplies',
            'machine_model_ids' => [$modelA->id],
            'is_active' => '1',
        ])->assertRedirect(route('stock.show', $product));

        $this->assertDatabaseHas('stock_products', ['id' => $product->id, 'name' => 'Sharp black toner cartridge']);
        $this->assertTrue($product->fresh()->machineModels()->whereKey($modelA->id)->exists());
        $this->assertFalse($product->fresh()->machineModels()->whereKey($modelB->id)->exists());
    }

    public function test_stock_cannot_be_moved_to_site_without_company_quantity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create();
        $product = StockProduct::create([
            'company_id' => $company->id,
            'name' => 'Waste toner box',
            'type' => StockProduct::TYPE_WASTE_BOX,
        ]);

        $this->actingAs($user)->post(route('stock.transfer'), [
            'stock_product_id' => $product->id,
            'site_id' => $site->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');
    }

    public function test_company_user_can_view_own_site_map(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);
        $client = Client::factory()->for($company)->create(['name' => 'Mapped Client']);
        $site = Site::factory()->for($client)->create(['name' => 'Mapped Site', 'latitude' => 51.5073510, 'longitude' => -0.1277580]);
        $model = MachineModel::factory()->for($company)->create();
        Machine::factory()->for($client)->for($site)->for($model)->create(['machine_name' => 'Mapped Copier']);

        $this->actingAs($user)->get(route('sites.map'))
            ->assertOk()
            ->assertSee('Site Map')
            ->assertSee('Mapped Site')
            ->assertSee('Mapped Copier');
    }
}
