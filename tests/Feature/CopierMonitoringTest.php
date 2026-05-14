<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ConsumableReading;
use App\Models\EmailSource;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MachineCredential;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\MeterReading;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Models\User;
use App\Services\Reports\IncomingEmailIngestionService;
use App\Services\Reports\MachineReportMatcher;
use App\Services\Reports\Parsers\SharpMxStatusEmailParser;
use App\Services\Reports\ReportingService;
use App\Services\Reports\ReportProcessingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CopierMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_sites_and_machines_can_be_created(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $model = MachineModel::factory()->create(['parser_type' => 'sharp_mx_status_email']);
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create(['serial_number' => '95012345']);

        $this->assertTrue($client->sites->contains($site));
        $this->assertTrue($client->machines->contains($machine));
        $this->assertTrue($site->machines->contains($machine));
        $this->assertSame('95012345', $machine->fresh()->serial_number);
    }

    public function test_company_admin_can_use_platform_master_machine_model(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $admin = User::factory()->for($client->company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $masterModel = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);

        $this->actingAs($admin)->post(route('machines.store'), [
            'client_id' => $client->id,
            'site_id' => $site->id,
            'machine_model_id' => $masterModel->id,
            'manufacturer_id' => $manufacturer->id,
            'serial_number' => 'MASTER-95012345',
            'machine_name' => 'Accounts MFP',
            'location' => 'Accounts',
            'ip_address' => '192.168.1.45',
            'expected_report_sender_email' => 'mfp@example.com',
            'is_active' => '1',
        ])->assertRedirect(route('machines.index'));

        $this->assertDatabaseHas('machines', [
            'client_id' => $client->id,
            'machine_model_id' => $masterModel->id,
            'serial_number' => 'MASTER-95012345',
            'manufacturer' => 'Sharp',
            'model' => 'MX-2630N',
        ]);
    }

    public function test_machine_create_requires_user_to_choose_manufacturer_and_model(): void
    {
        $client = Client::factory()->create();
        Site::factory()->for($client)->create();
        $admin = User::factory()->for($client->company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);

        $this->actingAs($admin)->get(route('machines.create'))
            ->assertOk()
            ->assertSee('Choose manufacturer')
            ->assertSee('Choose machine model')
            ->assertSee('Select a manufacturer first');
    }

    public function test_machine_create_derives_manufacturer_from_selected_machine_model(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $admin = User::factory()->for($client->company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $sharp = Manufacturer::findOrCreateByName('Sharp');
        $canon = Manufacturer::findOrCreateByName('Canon');
        $masterModel = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $sharp->id,
            'manufacturer' => $sharp->name,
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);

        $this->actingAs($admin)->post(route('machines.store'), [
            'client_id' => $client->id,
            'site_id' => $site->id,
            'machine_model_id' => $masterModel->id,
            'manufacturer_id' => $canon->id,
            'serial_number' => 'DERIVED-95012345',
            'machine_name' => 'Accounts MFP',
            'is_active' => '1',
        ])->assertRedirect(route('machines.index'));

        $this->assertDatabaseHas('machines', [
            'serial_number' => 'DERIVED-95012345',
            'manufacturer' => 'Sharp',
            'model' => 'MX-2630N',
        ]);
    }

    public function test_machine_model_uses_manufacturer_table_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN, 'company_id' => null]);

        $this->actingAs($admin)->post(route('machine-models.store'), [
            'manufacturer_name' => 'Sharp',
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
            'notes' => 'Master Sharp parser',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('machine-models.store'), [
            'manufacturer_name' => 'Sharp',
            'model_name' => 'MX-4070N',
            'parser_type' => 'sharp_mx_status_email',
        ])->assertRedirect();

        $this->assertSame(1, Manufacturer::where('slug', 'sharp')->count());
        $this->assertSame(2, MachineModel::where('manufacturer', 'Sharp')->count());
    }

    public function test_serial_number_must_be_unique(): void
    {
        Machine::factory()->create(['serial_number' => '95012345']);

        $this->expectException(QueryException::class);
        Machine::factory()->create(['serial_number' => '95012345']);
    }

    public function test_incoming_email_is_stored_before_parsing(): void
    {
        $email = app(IncomingEmailIngestionService::class)->store([
            'from_email' => 'copier@example.test',
            'to_email' => 'reports@example.test',
            'subject' => 'MX-2630N Status Message',
            'body_text' => $this->sharpFixture(),
            'raw_payload' => ['provider' => 'mailgun'],
        ], queue: false);

        $this->assertDatabaseHas('incoming_report_emails', [
            'id' => $email->id,
            'parse_status' => IncomingReportEmail::STATUS_PENDING,
        ]);
        $this->assertSame('mailgun', $email->raw_payload['provider']);
    }

    public function test_company_user_can_manually_pull_active_email_sources(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'configuration' => ['mailbox_protocol' => 'pop3'],
        ]);
        EmailSource::factory()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('reports:poll-imap', ['--source' => $source->id])
            ->andReturn(0);

        $this->actingAs($admin)
            ->post(route('incoming-report-emails.pull'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Manual pull finished. 0 new email(s) imported. Messages already imported from POP mailboxes are skipped on later pulls.');
    }

    public function test_manual_pull_ignores_inactive_email_sources(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'configuration' => ['mailbox_protocol' => 'pop3'],
            'is_active' => false,
        ]);

        Artisan::shouldReceive('call')->never();

        $this->actingAs($admin)
            ->post(route('incoming-report-emails.pull'))
            ->assertRedirect()
            ->assertSessionHasErrors('pull');
    }

    public function test_incoming_email_index_has_manual_pull_button(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)
            ->get(route('incoming-report-emails.index'))
            ->assertOk()
            ->assertSee('Pull emails')
            ->assertSee(route('incoming-report-emails.pull'), false);
    }

    public function test_incoming_email_index_orders_by_import_time(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $oldReceivedRecentImport = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'subject' => 'Recently imported old email',
            'received_at' => now()->subMonth(),
            'created_at' => now(),
        ]);
        $recentReceivedOldImport = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'subject' => 'Older import newer email',
            'received_at' => now(),
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('incoming-report-emails.index'))
            ->assertOk();

        $response->assertSeeInOrder([
            $oldReceivedRecentImport->subject,
            $recentReceivedOldImport->subject,
        ]);
    }

    public function test_manual_pull_processes_newly_imported_emails_immediately(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'configuration' => ['mailbox_protocol' => 'pop3'],
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('reports:poll-imap', ['--source' => $source->id])
            ->andReturnUsing(function () use ($company, $source) {
                IncomingReportEmail::factory()->create([
                    'company_id' => $company->id,
                    'body_text' => 'Serial Number : MANUAL-PULL-001',
                    'raw_payload' => ['email_source_id' => $source->id],
                    'parse_status' => IncomingReportEmail::STATUS_PENDING,
                ]);

                return 0;
            });

        $this->actingAs($admin)
            ->post(route('incoming-report-emails.pull'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Manual pull finished. 1 new email(s) imported.');

        $this->assertDatabaseHas('incoming_report_emails', [
            'body_text' => 'Serial Number : MANUAL-PULL-001',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);
    }

    public function test_email_matches_machine_by_serial_number(): void
    {
        $machine = $this->sharpMachine();
        $email = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture()]);

        $matched = app(MachineReportMatcher::class)->match($email);

        $this->assertTrue($matched->is($machine));
        $this->assertSame($machine->id, $email->fresh()->machine_id);
    }

    public function test_unmatched_email_is_kept_for_review(): void
    {
        $email = IncomingReportEmail::factory()->create(['body_text' => 'Serial Number : UNKNOWN']);

        $this->assertNull(app(MachineReportMatcher::class)->match($email));
        $this->assertSame(IncomingReportEmail::STATUS_UNMATCHED, $email->fresh()->parse_status);
    }

    public function test_unmatched_email_pages_show_detected_serial_number(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'body_text' => 'Serial Number : UNKNOWN-SERIAL-999',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);

        $this->actingAs($admin)->get(route('incoming-report-emails.index'))
            ->assertOk()
            ->assertSee('Detected: UNKNOWN-SERIAL-999');

        $this->actingAs($admin)->get(route('incoming-report-emails.show', $email))
            ->assertOk()
            ->assertSee('UNKNOWN-SERIAL-999');
    }

    public function test_creating_machine_rechecks_unmatched_emails_for_same_serial_number(): void
    {
        $company = Client::factory()->create()->company;
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Ricoh');
        $model = MachineModel::factory()->for($company)->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'IM C3000',
            'parser_type' => 'generic_counter_email',
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'machine_id' => null,
            'body_text' => "Device Report\nSerial No. = LATE-MATCH-001\nTotal Counter : 12345",
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);

        $this->actingAs($admin)->post(route('machines.store'), [
            'client_id' => $client->id,
            'site_id' => $site->id,
            'machine_model_id' => $model->id,
            'manufacturer_id' => $manufacturer->id,
            'serial_number' => 'LATE-MATCH-001',
            'machine_name' => 'Late matched device',
            'is_active' => '1',
        ])->assertRedirect(route('machines.index'));

        $email->refresh();

        $this->assertNotNull($email->machine_id);
        $this->assertSame(IncomingReportEmail::STATUS_PENDING_TEMPLATE, $email->parse_status);
    }

    public function test_sharp_mx_parser_extracts_counters_and_consumables(): void
    {
        $email = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture(), 'received_at' => now()]);
        $parsed = app(SharpMxStatusEmailParser::class)->parse($email);

        $this->assertSame('MX-2630N', $parsed->machineName);
        $this->assertSame('95012345', $parsed->serialNumber);
        $this->assertSame(123456, $parsed->totalCounter);
        $this->assertSame(99313, $parsed->monoCounter);
        $this->assertSame(22562, $parsed->colourCounter);
        $this->assertSame(62, $parsed->toners['black']);
        $this->assertSame('NORMAL', $parsed->paperTrayStatus['Tray 1']);
    }

    public function test_sharp_mx_parser_ignores_toner_status_suffix_when_extracting_percentage(): void
    {
        $body = str_replace('Black Toner : 62%', 'Black Toner : 9%|Replace Soon', $this->sharpFixture());
        $email = IncomingReportEmail::factory()->create(['body_text' => $body, 'received_at' => now()]);

        $parsed = app(SharpMxStatusEmailParser::class)->parse($email);

        $this->assertSame(9, $parsed->toners['black']);
    }

    public function test_sharp_mx_parser_extracts_real_counter_email_variant(): void
    {
        $email = IncomingReportEmail::factory()->create([
            'body_text' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'received_at' => now(),
        ]);

        $parsed = app(SharpMxStatusEmailParser::class)->parse($email);

        $this->assertNull($parsed->machineName);
        $this->assertSame('MX-2630N', $parsed->modelName);
        $this->assertSame('7505132800', $parsed->serialNumber);
        $this->assertSame('2026-05-13 12:37:43', $parsed->reportedAt->format('Y-m-d H:i:s'));
        $this->assertSame(249687, $parsed->totalCounter);
        $this->assertSame(36732, $parsed->monoCounter);
        $this->assertSame(212955, $parsed->colourCounter);
        $this->assertSame(329, $parsed->copyMonoCounter);
        $this->assertSame(7149, $parsed->copyColourCounter);
        $this->assertSame(36301, $parsed->printMonoCounter);
        $this->assertSame(205735, $parsed->printColourCounter);
        $this->assertSame(28184, $parsed->scanCounter);
        $this->assertSame(24, $parsed->toners['black']);
        $this->assertSame(61, $parsed->toners['cyan']);
        $this->assertSame(88, $parsed->toners['magenta']);
        $this->assertSame(81, $parsed->toners['yellow']);
        $this->assertSame(6, $parsed->raw['toner_lifecycle']['inserted_toner_number']['black']);
        $this->assertSame(5, $parsed->raw['toner_lifecycle']['inserted_toner_number']['magenta']);
        $this->assertSame(0, $parsed->raw['toner_lifecycle']['toner_nn_end']['cyan']);
        $this->assertSame(5, $parsed->raw['toner_lifecycle']['toner_end']['cyan']);
    }

    public function test_sharp_mx_parser_extracts_real_counter_email_variant_with_crlf_line_endings(): void
    {
        $email = IncomingReportEmail::factory()->create([
            'body_text' => str_replace("\n", "\r\n", file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt'))),
            'received_at' => now(),
        ]);

        $parsed = app(SharpMxStatusEmailParser::class)->parse($email);

        $this->assertSame(249687, $parsed->totalCounter);
        $this->assertSame(36732, $parsed->monoCounter);
        $this->assertSame(212955, $parsed->colourCounter);
        $this->assertSame(329, $parsed->copyMonoCounter);
        $this->assertSame(7149, $parsed->copyColourCounter);
        $this->assertSame(36301, $parsed->printMonoCounter);
        $this->assertSame(205735, $parsed->printColourCounter);
        $this->assertSame(28184, $parsed->scanCounter);
        $this->assertSame(6, $parsed->raw['toner_lifecycle']['inserted_toner_number']['black']);
        $this->assertSame(5, $parsed->raw['toner_lifecycle']['toner_end']['cyan']);
    }

    public function test_real_sharp_counter_email_variant_matches_and_processes(): void
    {
        $machine = $this->sharpMachine();
        $machine->update(['serial_number' => '7505132800']);
        $email = IncomingReportEmail::factory()->create([
            'body_text' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'received_at' => now(),
        ]);

        app(ReportProcessingService::class)->process($email);

        $this->assertDatabaseHas('incoming_report_emails', ['id' => $email->id, 'machine_id' => $machine->id, 'parse_status' => IncomingReportEmail::STATUS_PARSED]);
        $this->assertDatabaseHas('meter_readings', ['machine_id' => $machine->id, 'total_counter' => 249687, 'mono_counter' => 36732, 'colour_counter' => 212955]);
        $this->assertDatabaseHas('consumable_readings', ['machine_id' => $machine->id, 'consumable_type' => 'toner', 'colour' => 'black', 'percentage' => 24]);
        $this->assertSame(6, $email->fresh()->parsed_payload['raw']['toner_lifecycle']['inserted_toner_number']['black']);
        $this->assertSame(4, $email->fresh()->parsed_payload['raw']['toner_lifecycle']['toner_end']['black']);
    }

    public function test_reprocessing_updates_existing_meter_reading_for_same_email(): void
    {
        $machine = $this->sharpMachine();
        $machine->update(['serial_number' => '7505132800']);
        $body = str_replace("\n", "\r\n", file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')));
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $machine->client->company_id,
            'machine_id' => $machine->id,
            'body_text' => $body,
            'received_at' => '2026-05-13 12:37:43',
        ]);
        MeterReading::create([
            'machine_id' => $machine->id,
            'company_id' => $machine->client->company_id,
            'incoming_report_email_id' => $email->id,
            'reading_date' => '2026-05-13 12:37:43',
            'usage_unknown' => true,
        ]);

        app(ReportProcessingService::class)->process($email);

        $this->assertSame(1, MeterReading::where('incoming_report_email_id', $email->id)->count());
        $this->assertDatabaseHas('meter_readings', [
            'incoming_report_email_id' => $email->id,
            'total_counter' => 249687,
            'mono_counter' => 36732,
            'colour_counter' => 212955,
        ]);
    }

    public function test_generic_parser_uses_template_mappings_for_table_style_reports(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $manufacturer = Manufacturer::findOrCreateByName('Xerox');
        $model = MachineModel::factory()->create([
            'company_id' => $client->company_id,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'VersaLink C405',
            'parser_type' => 'generic_counter_email',
        ]);
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create([
            'serial_number' => 'XRX-DEMO-003',
            'manufacturer' => 'Xerox',
            'model' => 'VersaLink C405',
        ]);
        ReportTemplate::factory()->for($model, 'machineModel')->create([
            'company_id' => $client->company_id,
            'parser_type' => 'generic_counter_email',
            'is_active' => true,
            'parser_configuration' => [
                'serial_number_labels' => ['Serial'],
                'report_date_labels' => ['Timestamp'],
                'machine_name_labels' => ['Asset Name'],
                'model_name_labels' => ['Device Type'],
                'total_counter_labels' => ['Total Impressions'],
                'mono_counter_labels' => ['Black Impressions'],
                'colour_counter_labels' => ['Color Impressions'],
                'black_toner_percentage_labels' => ['Black Toner'],
                'black_inserted_toner_number_labels' => ['Black Inserted Toner Number'],
                'cyan_inserted_toner_number_labels' => ['Cyan Inserted Toner Number'],
            ],
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $client->company_id,
            'body_text' => $this->xeroxTableFixture(),
        ]);

        app(ReportProcessingService::class)->process($email);

        $this->assertDatabaseHas('incoming_report_emails', ['id' => $email->id, 'machine_id' => $machine->id, 'parse_status' => IncomingReportEmail::STATUS_PARSED]);
        $this->assertDatabaseHas('meter_readings', ['machine_id' => $machine->id, 'total_counter' => 98765, 'mono_counter' => 80220, 'colour_counter' => 18545]);
        $this->assertDatabaseHas('consumable_readings', ['machine_id' => $machine->id, 'consumable_type' => 'toner', 'colour' => 'black', 'percentage' => 9]);
        $this->assertDatabaseHas('consumable_readings', ['machine_id' => $machine->id, 'consumable_type' => 'waste_toner', 'status' => 'OK']);
        $this->assertSame(3, $email->fresh()->parsed_payload['raw']['toner_lifecycle']['inserted_toner_number']['black']);
        $this->assertSame(4, $email->fresh()->parsed_payload['raw']['toner_lifecycle']['inserted_toner_number']['cyan']);
    }

    public function test_generic_parser_supports_bracket_comma_reports(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $manufacturer = Manufacturer::findOrCreateByName('Konica Minolta');
        $model = MachineModel::factory()->create([
            'company_id' => $client->company_id,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'Generic Bracket Report',
            'parser_type' => 'generic_counter_email',
        ]);
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create([
            'serial_number' => 'A5C4121004367',
            'manufacturer' => $manufacturer->name,
            'model' => 'Generic Bracket Report',
        ]);
        ReportTemplate::factory()->for($model, 'machineModel')->create([
            'company_id' => $client->company_id,
            'parser_type' => 'generic_counter_email',
            'is_active' => true,
            'parser_configuration' => [
                'model_name_labels' => ['Model Name'],
                'serial_number_labels' => ['Serial Number'],
                'report_date_labels' => ['Send Date'],
                'total_counter_labels' => ['Total Counter'],
                'colour_counter_labels' => ['Total Color Counter'],
                'mono_counter_labels' => ['Total Black Counter'],
                'scan_counter_labels' => ['Total Scan/Fax Counter'],
            ],
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $client->company_id,
            'body_text' => $this->bracketCommaFixture(),
        ]);

        app(ReportProcessingService::class)->process($email);

        $this->assertDatabaseHas('incoming_report_emails', ['id' => $email->id, 'machine_id' => $machine->id, 'parse_status' => IncomingReportEmail::STATUS_PARSED]);
        $this->assertDatabaseHas('meter_readings', [
            'machine_id' => $machine->id,
            'total_counter' => 171461,
            'mono_counter' => 156291,
            'colour_counter' => 15170,
            'scan_counter' => 154875,
        ]);
    }

    public function test_matched_email_waits_for_template_when_machine_model_has_no_template(): void
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $model = MachineModel::factory()->create([
            'company_id' => $client->company_id,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);
        $machine = Machine::factory()->for($client)->for($site)->for($model)->create([
            'serial_number' => '95012345',
            'manufacturer' => 'Sharp',
            'model' => 'MX-2630N',
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $client->company_id,
            'body_text' => $this->sharpFixture(),
        ]);

        $this->assertNull(app(ReportProcessingService::class)->process($email));
        $email->refresh();

        $this->assertSame($machine->id, $email->machine_id);
        $this->assertSame(IncomingReportEmail::STATUS_PENDING_TEMPLATE, $email->parse_status);
        $this->assertStringContainsString('no active report template', $email->parse_error);
    }

    public function test_pending_template_email_can_start_template_wizard(): void
    {
        $machine = $this->sharpMachine(withTemplate: false);
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $machine->client->company_id,
            'machine_id' => $machine->id,
            'body_text' => $this->sharpFixture(),
            'subject' => 'MX-2630N Status Message',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);

        $this->actingAs($admin)->get(route('parser-queue.show', $email))
            ->assertOk()
            ->assertSee('Parser review')
            ->assertSee('Serial Number')
            ->assertSee('95012345')
            ->assertSee('Suggested Template')
            ->assertSee('serial_number_labels');
    }

    public function test_template_wizard_detects_bracket_comma_fields(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create();
        $model = MachineModel::factory()->for($company)->create([
            'manufacturer' => 'Konica Minolta',
            'model_name' => 'Generic Bracket Report',
            'parser_type' => 'generic_counter_email',
        ]);
        $machine = Machine::factory()->for($client)->for($site)->create([
            'machine_model_id' => $model->id,
            'serial_number' => 'A5C4121004367',
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'machine_id' => $machine->id,
            'body_text' => $this->bracketCommaFixture(),
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);

        $this->actingAs($admin)->get(route('parser-queue.show', $email))
            ->assertOk()
            ->assertSee('Serial Number')
            ->assertSee('A5C4121004367')
            ->assertSee('serial_number_labels')
            ->assertSee('Total Color Counter')
            ->assertSee('Total Black Counter')
            ->assertSee('Total Scan/Fax Counter');
    }

    public function test_creating_template_reprocesses_other_pending_emails_for_same_model(): void
    {
        $machine = $this->sharpMachine(withTemplate: false);
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $firstEmail = IncomingReportEmail::factory()->create([
            'company_id' => $machine->client->company_id,
            'body_text' => $this->sharpFixture(),
            'subject' => 'MX-2630N Status Message',
        ]);
        $secondEmail = IncomingReportEmail::factory()->create([
            'company_id' => $machine->client->company_id,
            'body_text' => $this->sharpFixture(),
            'subject' => 'MX-2630N Status Message',
        ]);

        app(ReportProcessingService::class)->process($firstEmail);
        app(ReportProcessingService::class)->process($secondEmail);

        $this->assertSame(IncomingReportEmail::STATUS_PENDING_TEMPLATE, $firstEmail->fresh()->parse_status);
        $this->assertSame(IncomingReportEmail::STATUS_PENDING_TEMPLATE, $secondEmail->fresh()->parse_status);

        $this->actingAs($admin)->post(route('parser-queue.approve-company', $firstEmail), [
            'parser_type' => 'sharp_mx_status_email',
            'parser_configuration' => json_encode(['serial_number_labels' => ['Serial Number']]),
        ])->assertRedirect(route('parser-queue.show', $firstEmail));

        $this->assertSame(IncomingReportEmail::STATUS_PARSED, $firstEmail->fresh()->parse_status);
        $this->assertSame(IncomingReportEmail::STATUS_PARSED, $secondEmail->fresh()->parse_status);
        $this->assertDatabaseHas('parser_review_logs', [
            'incoming_report_email_id' => $firstEmail->id,
            'action' => 'template_approved',
            'scope' => 'company',
        ]);
    }

    public function test_unmatched_email_can_start_template_wizard_from_detected_model(): void
    {
        $company = Client::factory()->create()->company;
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $platformAdmin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        MachineModel::factory()->for($company)->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => 'Sharp',
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);
        $email = IncomingReportEmail::factory()->create([
            'company_id' => $company->id,
            'machine_id' => null,
            'body_text' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'subject' => '- Status Message',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);

        $this->actingAs($admin)->get(route('incoming-report-emails.show', $email))
            ->assertOk()
            ->assertSee('Waiting for machine match')
            ->assertDontSee('Build template from email');

        $this->actingAs($platformAdmin)->get(route('parser-queue.show', $email))
            ->assertOk()
            ->assertSee('Parser review')
            ->assertSee('Machine match needed')
            ->assertSee('Device Model')
            ->assertSee('MX-2630N')
            ->assertSee('Black &amp; White Print Count', false)
            ->assertSee('36301')
            ->assertSee('Toner Residual (Bk)')
            ->assertSee('24%')
            ->assertSee('sharp_mx_status_email');
    }

    public function test_template_edit_shows_detected_fields_from_sample_body(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $model = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => 'Sharp',
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);
        $template = ReportTemplate::factory()->for($model, 'machineModel')->create([
            'company_id' => null,
            'sample_body' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'parser_type' => 'sharp_mx_status_email',
            'parser_configuration' => [
                'serial_number_labels' => ['Serial Number'],
                'print_mono_counter_labels' => ['Black & White Print Count'],
            ],
        ]);

        $this->actingAs($admin)->get(route('report-templates.edit', $template))
            ->assertOk()
            ->assertSee('Detected Labels')
            ->assertSee('Serial Number')
            ->assertSee('7505132800')
            ->assertSee('Black &amp; White Print Count', false)
            ->assertSee('36301')
            ->assertSee('Example Parsed Reading');
    }

    public function test_template_edit_suggests_mappings_when_configuration_is_empty(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $model = MachineModel::factory()->create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => 'Sharp',
            'model_name' => 'MX-2630N',
            'parser_type' => 'sharp_mx_status_email',
        ]);
        $template = ReportTemplate::factory()->for($model, 'machineModel')->create([
            'company_id' => null,
            'sample_body' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'parser_type' => 'sharp_mx_status_email',
            'parser_configuration' => [],
        ]);

        $this->actingAs($admin)->get(route('report-templates.edit', $template))
            ->assertOk()
            ->assertSee('Black &amp; White Print Count', false)
            ->assertSee('option value="Black &amp; White Print Count" selected', false)
            ->assertSee('Toner Residual (Bk)')
            ->assertSee('Toner Residual (C)')
            ->assertSee('Toner Residual (M)')
            ->assertSee('Toner Residual (Y)')
            ->assertSee('option value="Toner Residual (Bk)" selected', false)
            ->assertSee('option value="Toner Residual (C)" selected', false)
            ->assertSee('option value="Toner Residual (M)" selected', false)
            ->assertSee('option value="Toner Residual (Y)" selected', false)
            ->assertSee('Inserted Toner Number (Bk)')
            ->assertSee('Inserted Toner Number (C)')
            ->assertSee('Inserted Toner Number (M)')
            ->assertSee('Inserted Toner Number (Y)')
            ->assertSee('option value="Inserted Toner Number (Bk)" selected', false)
            ->assertSee('option value="Inserted Toner Number (C)" selected', false)
            ->assertSee('option value="Inserted Toner Number (M)" selected', false)
            ->assertSee('option value="Inserted Toner Number (Y)" selected', false);
    }

    public function test_machine_page_shows_inserted_toner_numbers_from_latest_report(): void
    {
        $machine = $this->sharpMachine();
        $machine->update(['serial_number' => '7505132800']);
        $admin = User::factory()->for($machine->client->company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $email = IncomingReportEmail::factory()->create([
            'body_text' => file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')),
            'received_at' => now(),
        ]);

        app(ReportProcessingService::class)->process($email);

        $this->actingAs($admin)->get(route('machines.show', $machine))
            ->assertOk()
            ->assertSee('Black inserted')
            ->assertSee('Cyan inserted')
            ->assertSee('6')
            ->assertSee('5');
    }

    public function test_machine_page_handles_missing_inserted_toner_numbers(): void
    {
        $machine = $this->sharpMachine();
        $admin = User::factory()->for($machine->client->company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $email = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture()]);

        app(ReportProcessingService::class)->process($email);

        $this->actingAs($admin)->get(route('machines.show', $machine))
            ->assertOk()
            ->assertSee('Inserted toner numbers were not included')
            ->assertDontSee('Black inserted');
    }

    public function test_processing_creates_meter_and_consumable_readings(): void
    {
        $machine = $this->sharpMachine();
        $email = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture()]);

        app(ReportProcessingService::class)->process($email);

        $this->assertDatabaseHas('incoming_report_emails', ['id' => $email->id, 'machine_id' => $machine->id, 'parse_status' => IncomingReportEmail::STATUS_PARSED]);
        $this->assertDatabaseHas('meter_readings', ['machine_id' => $machine->id, 'total_counter' => 123456]);
        $this->assertDatabaseHas('consumable_readings', ['machine_id' => $machine->id, 'consumable_type' => 'toner', 'colour' => 'black', 'percentage' => 62]);
    }

    public function test_duplicate_readings_are_prevented(): void
    {
        $this->sharpMachine();
        $emailA = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture()]);
        $emailB = IncomingReportEmail::factory()->create(['body_text' => $this->sharpFixture()]);

        app(ReportProcessingService::class)->process($emailA);
        app(ReportProcessingService::class)->process($emailB);

        $this->assertSame(1, MeterReading::count());
        $this->assertSame(5, ConsumableReading::count());
    }

    public function test_usage_is_calculated_from_consecutive_readings(): void
    {
        $machine = $this->sharpMachine();
        MeterReading::factory()->for($machine)->create(['reading_date' => Carbon::parse('2026-05-10 08:00:00'), 'total_counter' => 120000, 'mono_counter' => 97000, 'colour_counter' => 22000]);
        MeterReading::factory()->for($machine)->create(['reading_date' => Carbon::parse('2026-05-11 08:00:00'), 'total_counter' => 123456, 'mono_counter' => 99313, 'colour_counter' => 22562]);

        $usage = app(ReportingService::class)->machineDailyUsage($machine, Carbon::parse('2026-05-10'), Carbon::parse('2026-05-11'))->last();

        $this->assertSame(3456, $usage['total_usage']);
        $this->assertSame(2313, $usage['mono_usage']);
        $this->assertSame(562, $usage['colour_usage']);
    }

    public function test_counter_decrease_flags_possible_reset(): void
    {
        $machine = $this->sharpMachine();
        MeterReading::factory()->for($machine)->create(['reading_date' => Carbon::parse('2026-05-10 08:00:00'), 'total_counter' => 120000]);
        $reading = MeterReading::factory()->for($machine)->create(['reading_date' => Carbon::parse('2026-05-11 08:00:00'), 'total_counter' => 100]);

        $usage = app(ReportingService::class)->usageForReading($reading);

        $this->assertTrue($usage['counter_reset_detected']);
        $this->assertNull($usage['total_usage']);
    }

    public function test_machine_network_fields_and_encrypted_credentials_are_stored(): void
    {
        $machine = $this->sharpMachine();

        $machine->update([
            'hostname' => 'accounts-mfp',
            'mac_address' => '00:11:22:33:44:55',
            'subnet_mask' => '255.255.255.0',
            'gateway' => '192.168.1.1',
            'primary_dns' => '8.8.8.8',
            'dhcp_enabled' => false,
        ]);

        $credential = MachineCredential::factory()->for($machine)->create([
            'label' => 'Device admin',
            'username' => 'admin',
            'password' => 'plain-secret',
            'notes' => 'Secure note',
        ]);
        $raw = MachineCredential::query()->whereKey($credential->id)->toBase()->first();

        $this->assertSame('accounts-mfp', $machine->fresh()->hostname);
        $this->assertSame('plain-secret', $credential->fresh()->password);
        $this->assertNotSame('plain-secret', $raw->password);
        $this->assertNotSame('Secure note', $raw->notes);
    }

    private function sharpMachine(bool $withTemplate = true): Machine
    {
        $client = Client::factory()->create();
        $site = Site::factory()->for($client)->create();
        $manufacturer = Manufacturer::findOrCreateByName('Sharp');
        $model = MachineModel::factory()->create(['company_id' => $client->company_id, 'manufacturer_id' => $manufacturer->id, 'manufacturer' => $manufacturer->name, 'model_name' => 'MX-2630N', 'parser_type' => 'sharp_mx_status_email']);

        $machine = Machine::factory()->for($client)->for($site)->for($model)->create([
            'serial_number' => '95012345',
            'manufacturer' => 'Sharp',
            'model' => 'MX-2630N',
        ]);

        if ($withTemplate) {
            ReportTemplate::factory()->for($model, 'machineModel')->create([
                'company_id' => $client->company_id,
                'template_name' => 'Sharp MX status email',
                'parser_type' => 'sharp_mx_status_email',
                'is_active' => true,
            ]);
        }

        return $machine;
    }

    private function sharpFixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_status_email.txt'));
    }

    private function xeroxTableFixture(): string
    {
        return <<<'EMAIL'
Xerox Device Usage Notification
Timestamp|2026-05-13T11:05:00
Asset Name|Xerox Mapping Sample
Device Type|VersaLink C405
Serial|XRX-DEMO-003
IP|192.168.1.90

COUNTERS
Name|Value
Total Impressions|98765
Black Impressions|80220
Color Impressions|18545
Black Copy Impressions|21001
Color Copy Impressions|6120
Black Print Impressions|59219
Color Print Impressions|12425
Scan Images|7320

SUPPLIES
Name|Remaining|State
Black Toner|9%|Replace Soon
Cyan Toner|44%|OK
Magenta Toner|38%|OK
Yellow Toner|41%|OK
Black Inserted Toner Number|3
Cyan Inserted Toner Number|4
Waste Toner Container||OK

Device State|Attention
Service Required|No
EMAIL;
    }

    private function bracketCommaFixture(): string
    {
        return <<<'EMAIL'
[Model Name],
[Serial Number], A5C4121004367
[Send Date],14/05/26
[Total Counter],00171461
[Total Color Counter],00015170
[Total Black Counter],00156291
[Total Scan/Fax Counter],00154875
EMAIL;
    }
}
