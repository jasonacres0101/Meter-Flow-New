<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\IncomingReportEmail;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\Site;
use Illuminate\Database\Seeder;

class AiParserChallengeEmailSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'AI Parser Test Account'],
            [
                'account_reference' => 'AI-PARSER-TEST',
                'billing_email' => 'ai-parser-tests@example.test',
                'country' => 'United Kingdom',
                'is_active' => true,
            ],
        );

        $client = Client::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'AI Parser Test Client'],
            [
                'account_reference' => 'AI-TEST-CLIENT',
                'contact_email' => 'ai-parser-tests@example.test',
                'mono_ppc' => 0,
                'colour_ppc' => 0,
                'is_active' => true,
            ],
        );

        $site = Site::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'AI Parser Lab'],
            [
                'address_line_1' => '1 Parser Way',
                'city' => 'Testville',
                'postcode' => 'TE1 1ST',
                'contact_email' => 'ai-parser-tests@example.test',
                'is_active' => true,
            ],
        );

        $this->seedMatchedTemplateChallenges($company, $client, $site);
        $this->seedUnmatchedChallenges($company);
    }

    private function seedMatchedTemplateChallenges(Company $company, Client $client, Site $site): void
    {
        $konica = Manufacturer::findOrCreateByName('Konica Minolta');
        $model = MachineModel::firstOrCreate(
            ['company_id' => $company->id, 'manufacturer_id' => $konica->id, 'model_name' => 'AI Parser Generic'],
            ['manufacturer' => $konica->name, 'parser_type' => 'generic_counter_email'],
        );

        $machine = Machine::firstOrCreate(
            ['serial_number' => 'AI-MATCH-PIPE-001'],
            [
                'client_id' => $client->id,
                'site_id' => $site->id,
                'machine_model_id' => $model->id,
                'manufacturer' => $konica->name,
                'model' => 'AI Parser Generic',
                'machine_name' => 'AI Pipe Mapping Test',
                'location' => 'Parser Lab',
                'is_active' => true,
            ],
        );

        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - matched pipe toner report'],
            [
                'machine_id' => $machine->id,
                'from_email' => 'ai-pipe-device@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subMinutes(3),
                'body_text' => <<<TEXT
Machine Snapshot
Report Sent|2026-05-14 15:45:00
Device Label|AI Pipe Mapping Test
Model Ref|AI Parser Generic
Serial Ref|AI-MATCH-PIPE-001

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
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_matched_pipe'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                'parse_error' => 'AI test sample: matched machine, no active template yet.',
            ],
        );

        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - matched bracket comma report'],
            [
                'machine_id' => $machine->id,
                'from_email' => 'ai-bracket-device@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subMinutes(2),
                'body_text' => <<<TEXT
[Model Name], AI Parser Generic
[Serial Number], AI-MATCH-PIPE-001
[Send Date],14/05/26
[Total Counter],00171461
[Total Color Counter],00015170
[Total Black Counter],00156291
[Total Scan/Fax Counter],00154875
[Black Toner],38% OK
[Cyan Toner],72% OK
[Magenta Toner],19% LOW
[Yellow Toner],41% OK
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_matched_bracket_comma'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                'parse_error' => 'AI test sample: bracket/comma labels need a template mapping.',
            ],
        );

        $xerox = Manufacturer::findOrCreateByName('Xerox');
        $tableModel = MachineModel::firstOrCreate(
            ['company_id' => $company->id, 'manufacturer_id' => $xerox->id, 'model_name' => 'AI Table Parser'],
            ['manufacturer' => $xerox->name, 'parser_type' => 'generic_counter_email'],
        );

        $tableMachine = Machine::firstOrCreate(
            ['serial_number' => 'AI-MATCH-TABLE-002'],
            [
                'client_id' => $client->id,
                'site_id' => $site->id,
                'machine_model_id' => $tableModel->id,
                'manufacturer' => $xerox->name,
                'model' => 'AI Table Parser',
                'machine_name' => 'AI Table Mapping Test',
                'location' => 'Parser Lab',
                'is_active' => true,
            ],
        );

        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - matched table layout report'],
            [
                'machine_id' => $tableMachine->id,
                'from_email' => 'ai-table-device@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subSeconds(90),
                'body_text' => <<<TEXT
Device Information
Name|AI Table Mapping Test
Type|AI Table Parser
Serial|AI-MATCH-TABLE-002
Generated|2026-05-14T16:15:00

COUNTERS
Metric|Reading
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
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_matched_table'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                'parse_error' => 'AI test sample: table layout needs AI-assisted mapping.',
            ],
        );
    }

    private function seedUnmatchedChallenges(Company $company): void
    {
        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - unmatched pipe toner report'],
            [
                'machine_id' => null,
                'from_email' => 'ai-unmatched-pipe@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->subMinute(),
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
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_unmatched_pipe'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => null,
            ],
        );

        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - unmatched csv counter report'],
            [
                'machine_id' => null,
                'from_email' => 'ai-unmatched-csv@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now(),
                'body_text' => <<<TEXT
Field,Value,Comment
Device Name,CSV Counter Sample,AI test
Device Model,CSV-9000,Unknown machine
Serial Number,AI-DEMO-CSV-005,No machine should match
Report Date,2026-05-14 16:00:00,Generated sample
Total Pages,56890,All impressions
Black Impressions,50123,Mono usage
Color Impressions,6767,Colour usage
Black Toner,54%,OK
Cyan Toner,29%,OK
Magenta Toner,12%,LOW
Yellow Toner,63%,OK
Waste Toner,OK,Container status
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_unmatched_csv'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => null,
            ],
        );

        IncomingReportEmail::updateOrCreate(
            ['company_id' => $company->id, 'subject' => 'AI TEST - hidden serial recovery report'],
            [
                'machine_id' => null,
                'from_email' => 'ai-hidden-serial@example.test',
                'to_email' => 'reports@example.test',
                'received_at' => now()->addSecond(),
                'body_text' => <<<TEXT
Hidden Serial Recovery Sample
Report Sent|2026-05-14 16:20:00
Device Label|Training Room Recovery Copier
Model Ref|AI-UNKNOWN-5000
Asset ID|AI-DEMO-HIDDEN-006

Counters
Grand Total|00456789 pages
Mono Total|00391234 pages
Colour Total|00065555 pages
Scan Images|00008765

Consumables
Black Toner|47%|OK
Cyan Toner|52%|OK
Magenta Toner|31%|OK
Yellow Toner|28%|OK
Waste Toner Container||OK

The normal matcher should not read Asset ID as a serial number. Create a machine with serial AI-DEMO-HIDDEN-006, then use Serial Match Assistant to link and reprocess this email.
TEXT,
                'raw_payload' => ['seeded' => true, 'purpose' => 'ai_parser_challenge_hidden_serial_recovery'],
                'parsed_payload' => null,
                'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
                'parse_error' => null,
            ],
        );
    }
}
