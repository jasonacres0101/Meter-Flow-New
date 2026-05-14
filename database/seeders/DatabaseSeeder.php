<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\BillingSetting;
use App\Models\Company;
use App\Models\ConsumableReading;
use App\Models\EmailSource;
use App\Models\EngineerSkillProfile;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MachineCredential;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\MeterReading;
use App\Models\ParserDefinition;
use App\Models\ReportTemplate;
use App\Models\ServiceTicket;
use App\Models\ServiceAgreement;
use App\Models\Site;
use App\Models\TonerAlertSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $platformAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'role' => User::ROLE_PLATFORM_ADMIN,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $company = Company::firstOrCreate(
            ['name' => 'Acme Copier Services'],
            ['account_reference' => 'CO-0001', 'billing_email' => 'billing@example.com', 'is_active' => true],
        );

        $companyAdmin = User::firstOrCreate(
            ['email' => 'company-admin@example.com'],
            [
                'company_id' => $company->id,
                'name' => 'Company Admin',
                'role' => User::ROLE_COMPANY_ADMIN,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $platformAdmin->forceFill(['role' => User::ROLE_PLATFORM_ADMIN, 'is_active' => true])->save();
        $companyAdmin->forceFill(['company_id' => $company->id, 'role' => User::ROLE_COMPANY_ADMIN, 'is_active' => true])->save();

        $engineer = User::firstOrCreate(
            ['email' => 'engineer@example.com'],
            [
                'name' => 'Service Engineer',
                'company_id' => null,
                'role' => User::ROLE_ENGINEER,
                'is_active' => true,
                'password' => 'password',
            ],
        );
        $engineer->forceFill(['role' => User::ROLE_ENGINEER, 'is_active' => true])->save();
        $engineer->engineerCompanies()->syncWithoutDetaching([$company->id]);

        $this->seedParserDefinitions();
        $this->seedManufacturers();
        BillingSetting::current()->update([
            'monthly_machine_rate' => 5.00,
            'currency' => 'GBP',
            'snapshot_day' => 25,
            'payment_terms_days' => 14,
            'gocardless_enabled' => false,
            'gocardless_environment' => 'sandbox',
        ]);

        $sharp = Manufacturer::findOrCreateByName('Sharp');

        $engineer->engineerSkillProfile()->updateOrCreate(
            ['user_id' => $engineer->id],
            [
                'networking_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'vlan_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dhcp_static_ip_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dns_level' => EngineerSkillProfile::LEVEL_BASIC,
                'routing_level' => EngineerSkillProfile::LEVEL_BASIC,
                'firewall_level' => EngineerSkillProfile::LEVEL_BASIC,
                'notes' => 'Demo engineer supports Sharp MFPs and common printer networking tasks.',
            ],
        );
        $engineer->supportedManufacturers()->syncWithoutDetaching([$sharp->id => ['skill_level' => EngineerSkillProfile::LEVEL_ADVANCED]]);

        $machineModel = MachineModel::firstOrCreate(
            ['company_id' => $company->id, 'manufacturer_id' => $sharp->id, 'model_name' => 'MX-2630N'],
            ['manufacturer' => $sharp->name, 'parser_type' => 'sharp_mx_status_email'],
        );

        ReportTemplate::updateOrCreate(
            ['machine_model_id' => $machineModel->id, 'template_name' => 'Sharp MX status email'],
            [
                'company_id' => $company->id,
                'sample_subject' => 'MX-2630N Status Message',
                'sample_body' => file_exists(base_path('tests/Fixtures/sharp_mx_2630n_status_email.txt'))
                    ? file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_status_email.txt'))
                    : 'Serial Number : 95012345',
                'parser_type' => 'sharp_mx_status_email',
                'parser_configuration' => [],
                'is_active' => true,
            ],
        );

        EmailSource::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Demo Gmail Reports Mailbox'],
            [
                'provider' => EmailSource::PROVIDER_GMAIL,
                'auth_type' => EmailSource::AUTH_BASIC,
                'mailbox_email' => 'copier-reports@example.com',
                'username' => 'copier-reports@example.com',
                'password' => 'demo-app-password',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'encryption' => 'ssl',
                'folder' => 'INBOX',
                'mark_as_seen' => true,
                'delete_after_ingest' => false,
                'configuration' => [],
                'is_active' => true,
            ],
        );

        EmailSource::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Demo Office 365 Reports Mailbox'],
            [
                'provider' => EmailSource::PROVIDER_OFFICE365,
                'auth_type' => EmailSource::AUTH_MICROSOFT_GRAPH,
                'mailbox_email' => 'copier-reports@example.com',
                'username' => 'copier-reports@example.com',
                'password' => null,
                'imap_host' => null,
                'imap_port' => null,
                'encryption' => null,
                'folder' => 'Inbox',
                'oauth_tenant_id' => '00000000-0000-0000-0000-000000000000',
                'oauth_client_id' => '11111111-1111-1111-1111-111111111111',
                'oauth_client_secret' => 'demo-client-secret',
                'oauth_scope' => 'https://graph.microsoft.com/.default',
                'oauth_status' => 'configured',
                'mark_as_seen' => true,
                'delete_after_ingest' => false,
                'configuration' => ['auth_mode' => 'client_credentials'],
                'is_active' => true,
            ],
        );

        TonerAlertSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'warning_threshold' => 25,
                'critical_threshold' => 10,
                'alert_black' => true,
                'alert_cyan' => true,
                'alert_magenta' => true,
                'alert_yellow' => true,
                'include_in_dashboard' => true,
                'notification_emails' => ['service@example.com'],
                'is_active' => true,
            ],
        );

        $machines = $this->seedDemoEstate($company, $machineModel);
        $this->seedServiceTickets($company, $companyAdmin, $engineer, $machines);
        $this->seedThreeMonthsOfReports($company, $machines);
        $this->seedSampleEmailsForTemplateTesting($company, $machines);
    }

    private function seedParserDefinitions(): void
    {
        foreach (ParserDefinition::builtInDefinitions() as $key => $name) {
            ParserDefinition::updateOrCreate(
                ['parser_key' => $key],
                [
                    'name' => $name,
                    'engine_type' => $key,
                    'default_configuration' => [],
                    'notes' => 'Built-in parser engine available to SaaS admins and tenants.',
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedManufacturers(): void
    {
        collect([
            'Brother',
            'Canon',
            'Develop',
            'Epson',
            'Fujifilm Business Innovation',
            'HP',
            'Konica Minolta',
            'Kyocera',
            'Lexmark',
            'Olivetti',
            'OKI',
            'Panasonic',
            'Ricoh',
            'Riso',
            'Samsung',
            'Sharp',
            'Toshiba',
            'Utax',
            'Xerox',
        ])->each(fn (string $name) => Manufacturer::findOrCreateByName($name));
    }

    /**
     * @return array<int, Machine>
     */
    private function seedDemoEstate(Company $company, MachineModel $machineModel): array
    {
        $estate = [
            [
                'client' => 'Acme Legal LLP',
                'pricing' => ['mono_ppc' => 0.90, 'colour_ppc' => 5.20],
                'sites' => [
                    'London HQ' => [
                        'coordinates' => ['latitude' => 51.5073510, 'longitude' => -0.1277580],
                        'pricing' => ['mono_ppc_override' => 0.85, 'colour_ppc_override' => 4.95],
                        'machines' => [
                            ['serial' => '95012345', 'name' => 'Accounts Copier', 'location' => 'Accounts', 'ip' => '192.168.1.45', 'base' => 123456],
                            ['serial' => '95012346', 'name' => 'Reception MFP', 'location' => 'Reception', 'ip' => '192.168.1.46', 'base' => 84210],
                        ],
                    ],
                    'Manchester Office' => [
                        'coordinates' => ['latitude' => 53.4807590, 'longitude' => -2.2426310],
                        'machines' => [
                            ['serial' => '95022345', 'name' => 'Litigation Printer', 'location' => 'Litigation', 'ip' => '192.168.2.45', 'base' => 101880],
                        ],
                    ],
                ],
            ],
            [
                'client' => 'Northbank Accountants',
                'pricing' => ['mono_ppc' => 0.75, 'colour_ppc' => 4.80],
                'sites' => [
                    'Birmingham HQ' => [
                        'coordinates' => ['latitude' => 52.4862440, 'longitude' => -1.8904010],
                        'machines' => [
                            ['serial' => '96012345', 'name' => 'Tax Team Copier', 'location' => 'Tax Department', 'ip' => '10.20.1.31', 'base' => 156700],
                            ['serial' => '96012346', 'name' => 'Payroll Printer', 'location' => 'Payroll', 'ip' => '10.20.1.32', 'base' => 66750, 'pricing' => ['mono_ppc_override' => 0.70, 'colour_ppc_override' => 4.55]],
                        ],
                    ],
                ],
            ],
            [
                'client' => 'Harbour Medical Group',
                'pricing' => ['mono_ppc' => 1.10, 'colour_ppc' => 6.00],
                'sites' => [
                    'Main Surgery' => [
                        'coordinates' => ['latitude' => 50.8197670, 'longitude' => -1.0879770],
                        'pricing' => ['mono_ppc_override' => 1.00, 'colour_ppc_override' => 5.75],
                        'machines' => [
                            ['serial' => '97012345', 'name' => 'Front Desk MFP', 'location' => 'Front Desk', 'ip' => '172.16.5.20', 'base' => 198420],
                        ],
                    ],
                    'East Clinic' => [
                        'coordinates' => ['latitude' => 50.8225300, 'longitude' => -0.1371630],
                        'machines' => [
                            ['serial' => '97022345', 'name' => 'Clinic Printer', 'location' => 'Nurses Station', 'ip' => '172.16.6.20', 'base' => 72130],
                        ],
                    ],
                ],
            ],
        ];

        $machines = [];

        foreach ($estate as $clientData) {
            $client = Client::updateOrCreate(
                ['company_id' => $company->id, 'name' => $clientData['client']],
                array_merge(['is_active' => true, 'included_mono_pages' => 500, 'included_colour_pages' => 100], $clientData['pricing']),
            );

            foreach ($clientData['sites'] as $siteName => $siteData) {
                $site = Site::updateOrCreate(
                    ['client_id' => $client->id, 'name' => $siteName],
                    array_merge([
                        'is_active' => true,
                        'mono_ppc_override' => null,
                        'colour_ppc_override' => null,
                        'latitude' => $siteData['coordinates']['latitude'] ?? null,
                        'longitude' => $siteData['coordinates']['longitude'] ?? null,
                    ], $siteData['pricing'] ?? []),
                );

                foreach ($siteData['machines'] as $machineData) {
                    $machines[] = Machine::updateOrCreate(
                        ['serial_number' => $machineData['serial']],
                        [
                            'client_id' => $client->id,
                            'site_id' => $site->id,
                            'machine_model_id' => $machineModel->id,
                            'manufacturer' => 'Sharp',
                            'model' => 'MX-2630N',
                            'machine_name' => $machineData['name'],
                            'location' => $machineData['location'],
                            'ip_address' => $machineData['ip'],
                            'hostname' => str($machineData['name'])->slug('-')->append('-mfp')->toString(),
                            'mac_address' => '00:1A:2B:'.str_pad((string) (($machineData['base'] ?? 100000) % 99), 2, '0', STR_PAD_LEFT).':44:55',
                            'subnet_mask' => '255.255.255.0',
                            'gateway' => preg_replace('/\.\d+$/', '.1', $machineData['ip']),
                            'primary_dns' => '8.8.8.8',
                            'secondary_dns' => '1.1.1.1',
                            'network_vlan' => $machineData['vlan'] ?? 'Printer VLAN',
                            'snmp_version' => 'v2c',
                            'snmp_community' => 'public',
                            'dhcp_enabled' => false,
                            'network_notes' => 'Static address reserved for device reporting and remote admin.',
                            'required_networking_level' => 'advanced',
                            'required_vlan_level' => filled($machineData['vlan'] ?? null) ? 'advanced' : 'basic',
                            'required_dhcp_static_ip_level' => 'advanced',
                            'required_dns_level' => 'basic',
                            'required_routing_level' => 'basic',
                            'required_firewall_level' => 'basic',
                            'expected_report_sender_email' => 'copier-'.$machineData['serial'].'@devices.example.test',
                            'mono_ppc_override' => $machineData['pricing']['mono_ppc_override'] ?? null,
                            'colour_ppc_override' => $machineData['pricing']['colour_ppc_override'] ?? null,
                            'is_active' => true,
                        ],
                    );

                    $machine = end($machines);
                    $agreement = ServiceAgreement::updateOrCreate(
                        ['company_id' => $company->id, 'agreement_number' => 'SA-MACHINE-'.$machine->id.'-'.today()->format('Y'), 'starts_on' => today()->subMonths(6)->startOfMonth()->toDateString()],
                        [
                            'client_id' => null,
                            'site_id' => null,
                            'machine_id' => null,
                            'ends_on' => null,
                            'mono_ppc' => $machine->mono_ppc_override ?? $site->mono_ppc_override ?? $client->mono_ppc,
                            'colour_ppc' => $machine->colour_ppc_override ?? $site->colour_ppc_override ?? $client->colour_ppc,
                            'included_mono_pages' => $machine->included_mono_pages_override ?? $site->included_mono_pages_override ?? $client->included_mono_pages,
                            'included_colour_pages' => $machine->included_colour_pages_override ?? $site->included_colour_pages_override ?? $client->included_colour_pages,
                            'is_active' => true,
                        ],
                    );
                    $agreement->machines()->syncWithoutDetaching([$machine->id]);
                }
            }
        }

        return $machines;
    }

    /**
     * @param  array<int, Machine>  $machines
     */
    private function seedThreeMonthsOfReports(Company $company, array $machines): void
    {
        $start = today()->subDays(89);

        foreach ($machines as $index => $machine) {
            $base = 60000 + ($index * 28500);
            $mono = (int) ($base * 0.72);
            $colour = (int) ($base * 0.28);
            $scan = 10000 + ($index * 900);
            $faxSent = 200 + ($index * 20);
            $faxReceived = 180 + ($index * 18);
            $toners = [
                'black' => 92 - ($index * 3),
                'cyan' => 80 - ($index * 2),
                'magenta' => 76 - ($index * 2),
                'yellow' => 72 - ($index * 2),
            ];

            for ($day = 0; $day < 90; $day++) {
                $date = $start->copy()->addDays($day)->setTime(8, ($index * 7) % 55, 1);
                $weekdayFactor = $date->isWeekend() ? 0.28 : 1;
                $dailyMono = (int) round((95 + (($day + $index) % 9) * 17 + ($index * 13)) * $weekdayFactor);
                $dailyColour = (int) round((28 + (($day + ($index * 2)) % 7) * 8 + ($index * 5)) * $weekdayFactor);

                $mono += $dailyMono;
                $colour += $dailyColour;
                $scan += (int) round((18 + (($day + $index) % 6) * 5) * $weekdayFactor);
                $faxSent += (int) round((($day + $index) % 3) * $weekdayFactor);
                $faxReceived += (int) round((($day + $index + 1) % 3) * $weekdayFactor);

                $copyMono = (int) round($mono * 0.55);
                $copyColour = (int) round($colour * 0.40);
                $printMono = $mono - $copyMono;
                $printColour = $colour - $copyColour;
                $total = $mono + $colour;

                foreach ($toners as $colourName => $percentage) {
                    $dropEvery = $colourName === 'black' ? 3 : 5;
                    if ($day > 0 && $day % $dropEvery === 0) {
                        $toners[$colourName] = max(6, $percentage - 1);
                    }

                    if ($toners[$colourName] <= 8 && $day % 21 === 0) {
                        $toners[$colourName] = 96;
                    }
                }

                $email = IncomingReportEmail::firstOrCreate(
                    [
                        'machine_id' => $machine->id,
                        'received_at' => $date,
                        'subject' => 'MX-2630N Status Message',
                    ],
                    [
                        'company_id' => $company->id,
                        'from_email' => $machine->expected_report_sender_email,
                        'to_email' => 'reports@example.test',
                        'body_text' => $this->sharpBody($machine, $date, $total, $copyMono, $copyColour, $printMono, $printColour, $scan, $faxSent, $faxReceived, $toners),
                        'raw_payload' => ['seeded' => true, 'provider' => 'demo'],
                        'parsed_payload' => [
                            'serial_number' => $machine->serial_number,
                            'reported_at' => $date->toIso8601String(),
                            'total_counter' => $total,
                            'toners' => $toners,
                        ],
                        'parse_status' => IncomingReportEmail::STATUS_PARSED,
                    ],
                );

                MeterReading::updateOrCreate(
                    ['machine_id' => $machine->id, 'reading_date' => $date],
                    [
                        'company_id' => $company->id,
                        'incoming_report_email_id' => $email->id,
                        'total_counter' => $total,
                        'mono_counter' => $mono,
                        'colour_counter' => $colour,
                        'copy_mono_counter' => $copyMono,
                        'copy_colour_counter' => $copyColour,
                        'print_mono_counter' => $printMono,
                        'print_colour_counter' => $printColour,
                        'scan_counter' => $scan,
                        'fax_sent_counter' => $faxSent,
                        'fax_received_counter' => $faxReceived,
                        'current_status' => $day % 37 === 0 ? 'LOW TONER' : 'READY',
                        'paper_tray_status' => ['Tray 1' => 'NORMAL', 'Tray 2' => $day % 29 === 0 ? 'LOW' : 'NORMAL', 'Bypass Tray' => 'NORMAL'],
                        'service_status' => 'NORMAL',
                        'usage_unknown' => $day === 0,
                        'counter_reset_detected' => false,
                    ],
                );

                foreach ($toners as $colourName => $percentage) {
                    ConsumableReading::updateOrCreate(
                        [
                            'machine_id' => $machine->id,
                            'consumable_type' => 'toner',
                            'colour' => $colourName,
                            'reading_date' => $date,
                        ],
                        [
                            'company_id' => $company->id,
                            'incoming_report_email_id' => $email->id,
                            'percentage' => $percentage,
                            'status' => $percentage <= 15 ? 'LOW' : 'NORMAL',
                        ],
                    );
                }
            }
        }
    }

    /**
     * @param  array<int, Machine>  $machines
     */
    private function seedSampleEmailsForTemplateTesting(Company $company, array $machines): void
    {
        IncomingReportEmail::where('company_id', $company->id)
            ->where('subject', 'like', 'SAMPLE -%')
            ->delete();

        $sharpMachine = $machines[0] ?? null;

        if ($sharpMachine) {
            IncomingReportEmail::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subject' => 'SAMPLE - parsed Sharp MX report',
                ],
                [
                    'machine_id' => $sharpMachine->id,
                    'from_email' => 'sample-sharp@devices.example.test',
                    'to_email' => 'reports@example.test',
                    'received_at' => now()->subMinutes(20),
                    'body_text' => file_exists(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt'))
                        ? str_replace('7505132800', $sharpMachine->serial_number, file_get_contents(base_path('tests/Fixtures/sharp_mx_2630n_counter_email_v2.txt')))
                        : $this->sharpBody($sharpMachine, now(), 250000, 55000, 9000, 44000, 14000, 18000, 500, 480, ['black' => 24, 'cyan' => 61, 'magenta' => 88, 'yellow' => 81]),
                    'raw_payload' => ['seeded' => true, 'purpose' => 'parsed_template_example'],
                    'parsed_payload' => [
                        'serial_number' => $sharpMachine->serial_number,
                        'template_status' => 'template_available',
                    ],
                    'parse_status' => IncomingReportEmail::STATUS_PARSED,
                    'parse_error' => null,
                ],
            );
        }

        $ricoh = Manufacturer::findOrCreateByName('Ricoh');
        $ricohModel = MachineModel::updateOrCreate(
            ['company_id' => $company->id, 'manufacturer_id' => $ricoh->id, 'model_name' => 'IM C3000'],
            ['manufacturer' => $ricoh->name, 'parser_type' => 'generic_counter_email'],
        );
        $client = Client::where('company_id', $company->id)->where('name', 'Acme Legal LLP')->first()
            ?? Client::where('company_id', $company->id)->first();
        $site = $client?->sites()->first();

        if ($client && $site) {
            $pendingMachine = Machine::updateOrCreate(
                ['serial_number' => 'RICOH-DEMO-001'],
                [
                    'client_id' => $client->id,
                    'site_id' => $site->id,
                    'machine_model_id' => $ricohModel->id,
                    'manufacturer' => 'Ricoh',
                    'model' => 'IM C3000',
                    'machine_name' => 'Template Wizard Sample',
                    'location' => 'IT build room',
                    'ip_address' => '192.168.1.88',
                    'expected_report_sender_email' => 'sample-ricoh@devices.example.test',
                    'is_active' => true,
                ],
            );

            IncomingReportEmail::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subject' => 'SAMPLE - template needed Ricoh report',
                ],
                [
                    'machine_id' => $pendingMachine->id,
                    'from_email' => 'sample-ricoh@devices.example.test',
                    'to_email' => 'reports@example.test',
                    'received_at' => now()->subMinutes(10),
                    'body_text' => $this->ricohSampleBody($pendingMachine),
                    'raw_payload' => ['seeded' => true, 'purpose' => 'pending_template_wizard'],
                    'parsed_payload' => null,
                    'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                    'parse_error' => 'Matched machine by serial number, but no active report template exists for this manufacturer and model.',
                ],
            );

            $canon = Manufacturer::findOrCreateByName('Canon');
            $canonModel = MachineModel::updateOrCreate(
                ['company_id' => $company->id, 'manufacturer_id' => $canon->id, 'model_name' => 'imageRUNNER ADVANCE C3530i'],
                ['manufacturer' => $canon->name, 'parser_type' => 'generic_counter_email'],
            );
            $canonMachine = Machine::updateOrCreate(
                ['serial_number' => 'CANON-DEMO-002'],
                [
                    'client_id' => $client->id,
                    'site_id' => $site->id,
                    'machine_model_id' => $canonModel->id,
                    'manufacturer' => 'Canon',
                    'model' => 'imageRUNNER ADVANCE C3530i',
                    'machine_name' => 'Canon Mapping Sample',
                    'location' => 'Reception test bench',
                    'ip_address' => '192.168.1.89',
                    'expected_report_sender_email' => 'sample-canon@devices.example.test',
                    'is_active' => true,
                ],
            );

            IncomingReportEmail::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subject' => 'SAMPLE - awkward Canon labels',
                ],
                [
                    'machine_id' => $canonMachine->id,
                    'from_email' => 'sample-canon@devices.example.test',
                    'to_email' => 'reports@example.test',
                    'received_at' => now()->subMinutes(8),
                    'body_text' => $this->canonAwkwardSampleBody($canonMachine),
                    'raw_payload' => ['seeded' => true, 'purpose' => 'parser_mapping_labels'],
                    'parsed_payload' => null,
                    'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                    'parse_error' => 'Matched machine by serial number, but the report uses non-standard labels. Create a template and map the fields.',
                ],
            );

            $xerox = Manufacturer::findOrCreateByName('Xerox');
            $xeroxModel = MachineModel::updateOrCreate(
                ['company_id' => $company->id, 'manufacturer_id' => $xerox->id, 'model_name' => 'VersaLink C405'],
                ['manufacturer' => $xerox->name, 'parser_type' => 'generic_counter_email'],
            );
            $xeroxMachine = Machine::updateOrCreate(
                ['serial_number' => 'XRX-DEMO-003'],
                [
                    'client_id' => $client->id,
                    'site_id' => $site->id,
                    'machine_model_id' => $xeroxModel->id,
                    'manufacturer' => 'Xerox',
                    'model' => 'VersaLink C405',
                    'machine_name' => 'Xerox Mapping Sample',
                    'location' => 'Accounts test bench',
                    'ip_address' => '192.168.1.90',
                    'expected_report_sender_email' => 'sample-xerox@devices.example.test',
                    'is_active' => true,
                ],
            );

            IncomingReportEmail::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'subject' => 'SAMPLE - table style Xerox counters',
                ],
                [
                    'machine_id' => $xeroxMachine->id,
                    'from_email' => 'sample-xerox@devices.example.test',
                    'to_email' => 'reports@example.test',
                    'received_at' => now()->subMinutes(7),
                    'body_text' => $this->xeroxTableSampleBody($xeroxMachine),
                    'raw_payload' => ['seeded' => true, 'purpose' => 'parser_mapping_table_layout'],
                    'parsed_payload' => null,
                    'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                    'parse_error' => 'Matched machine by serial number, but the counter data is in a table-style layout. Create a template and map the fields.',
                ],
            );
        }

        IncomingReportEmail::updateOrCreate(
            [
                'company_id' => $company->id,
                'subject' => 'SAMPLE - unmatched serial report',
            ],
            [
                'machine_id' => null,
                'from_email' => 'unknown-device@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subMinutes(5),
                'body_text' => <<<TEXT
Device Report
Serial Number : UNKNOWN-SERIAL-999
Model Name : Unknown Device
Total Counter : 12345
Black Toner : 44%

This sample is intentionally unmatched so the manual review workflow can be tested.
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'unmatched_review'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => null,
            ],
        );

        IncomingReportEmail::updateOrCreate(
            [
                'company_id' => $company->id,
                'subject' => 'SAMPLE - AI parser challenge unmatched report',
            ],
            [
                'machine_id' => null,
                'from_email' => 'ai-test-device@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subMinutes(4),
                'body_text' => <<<TEXT
AI Parser Challenge Report
Report Sent|2026-05-14 15:45:00
Device Label|Training Room Demo Copier
Model Ref|AI-UNKNOWN-5000
Serial Ref|AI-DEMO-NOMATCH-004

Counters
Grand Total|00123456 pages
Mono Total|00102030 pages
Colour Total|00021426 pages
Scan Images|00005678

Consumables
Black Toner|38%|OK
Cyan Toner|72%|OK
Magenta Toner|19%|LOW
Yellow Toner|41%|OK
Waste Toner Container||OK

This sample intentionally has no matching machine. Use Parser Queue > Ask AI for mapping to test AI suggestions before creating or matching the machine.
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_unmatched'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => null,
            ],
        );
    }

    /**
     * @param  array<int, Machine>  $machines
     */
    private function seedServiceTickets(Company $company, User $openedBy, User $engineer, array $machines): void
    {
        $machine = $machines[0] ?? null;

        if (! $machine) {
            return;
        }

        $ticket = ServiceTicket::updateOrCreate(
            ['ticket_number' => 'ST-DEMO-001'],
            [
                'company_id' => $company->id,
                'client_id' => $machine->client_id,
                'site_id' => $machine->site_id,
                'machine_id' => $machine->id,
                'opened_by_user_id' => $openedBy->id,
                'assigned_engineer_id' => $engineer->id,
                'title' => 'Recurring paper jam in tray 2',
                'issue_type' => 'repair',
                'priority' => 'high',
                'status' => ServiceTicket::STATUS_SCHEDULED,
                'description' => 'Customer reports repeated paper jams from tray 2 during morning print runs.',
                'scheduled_for' => now()->addDay()->setTime(10, 30),
            ],
        );

        $ticket->updates()->firstOrCreate(
            ['user_id' => $engineer->id, 'status' => ServiceTicket::STATUS_SCHEDULED],
            ['scheduled_for' => $ticket->scheduled_for, 'notes' => 'Engineer visit scheduled. Will inspect tray rollers and feed path.'],
        );

        MachineCredential::updateOrCreate(
            ['machine_id' => $machine->id, 'label' => 'Device web admin'],
            [
                'created_by_user_id' => $openedBy->id,
                'username' => 'admin',
                'password' => 'demo-password',
                'url' => 'http://'.$machine->ip_address,
                'notes' => 'Demo encrypted credential for the web admin console.',
                'last_rotated_at' => now()->subMonth(),
            ],
        );
    }

    /**
     * @param  array<string, int>  $toners
     */
    private function sharpBody(Machine $machine, Carbon $date, int $total, int $copyMono, int $copyColour, int $printMono, int $printColour, int $scan, int $faxSent, int $faxReceived, array $toners): string
    {
        return <<<TEXT
Date : {$date->format('Y/m/d H:i:s')}

Machine Name : {$machine->model}
Machine Address : {$machine->ip_address}
Serial Number : {$machine->serial_number}

Current Status : READY

[Counter Information]

Total Counter
{$total}

Copy Counter
B/W : {$copyMono}
Color : {$copyColour}

Printer Counter
B/W : {$printMono}
Color : {$printColour}

Scanner Counter
{$scan}

FAX Counter
Send : {$faxSent}
Receive : {$faxReceived}

[Toner Information]

Black Toner : {$toners['black']}%
Cyan Toner : {$toners['cyan']}%
Magenta Toner : {$toners['magenta']}%
Yellow Toner : {$toners['yellow']}%

[Paper Information]

Tray 1 : NORMAL
Tray 2 : NORMAL
Bypass Tray : NORMAL

[Maintenance Information]

Maintenance Counter : NORMAL
Waste Toner : NORMAL

No service is required.

This message has been generated automatically by the device.
TEXT;
    }

    private function ricohSampleBody(Machine $machine): string
    {
        return <<<TEXT
Report Date : 2026/05/13 09:15:00
Device Name : {$machine->machine_name}
Manufacturer : Ricoh
Model Name : {$machine->model}
Serial Number : {$machine->serial_number}
IPv4 Address : {$machine->ip_address}

[Meter Summary]
Total Counter : 88421
Black & White Total : 70210
Colour Total : 18211
Copy Black & White : 31102
Copy Colour : 6205
Print Black & White : 39108
Print Colour : 12006
Scanner Count : 4120

[Supplies]
Black Toner : 39%
Cyan Toner : 72%
Magenta Toner : 68%
Yellow Toner : 65%
Waste Toner : NORMAL

[Device Status]
Current Status : READY
Service Status : NORMAL
Paper Tray 1 : NORMAL
Paper Tray 2 : LOW
TEXT;
    }

    private function canonAwkwardSampleBody(Machine $machine): string
    {
        return <<<TEXT
Device Notification
Generated On = 13-05-2026 10:42

Unit ID = {$machine->machine_name}
Product = {$machine->model}
Serial No. = {$machine->serial_number}
Network Address = {$machine->ip_address}

Meter Readings
Grand Life Count = 144,782
Mono Life Count = 101,540
Colour Life Count = 43,242
Copies Mono = 31,440
Copies Colour = 14,002
Prints Mono = 70,100
Prints Colour = 29,240
Scans Total = 8,931

Supply Remaining
K Cartridge Remaining = 18 percent
C Cartridge Remaining = 53 percent
M Cartridge Remaining = 47 percent
Y Cartridge Remaining = 62 percent
Waste Bottle = Almost Full

Condition = Ready
Service Message = None
TEXT;
    }

    private function xeroxTableSampleBody(Machine $machine): string
    {
        return <<<TEXT
Xerox Device Usage Notification
Timestamp|2026-05-13T11:05:00
Asset Name|{$machine->machine_name}
Device Type|{$machine->model}
Serial|{$machine->serial_number}
IP|{$machine->ip_address}

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
Waste Toner Container||OK

Device State|Attention
Service Required|No
TEXT;
    }
}
