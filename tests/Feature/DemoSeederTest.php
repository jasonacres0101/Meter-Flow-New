<?php

namespace Tests\Feature;

use App\Models\IncomingReportEmail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_includes_sample_emails_for_template_testing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - parsed Sharp MX report',
            'parse_status' => IncomingReportEmail::STATUS_PARSED,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - template needed Ricoh report',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - unmatched serial report',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - awkward Canon labels',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - table style Xerox counters',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'SAMPLE - AI parser challenge unmatched report',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);

        $pending = IncomingReportEmail::where('subject', 'SAMPLE - template needed Ricoh report')->firstOrFail();
        $aiChallenge = IncomingReportEmail::where('subject', 'SAMPLE - AI parser challenge unmatched report')->firstOrFail();

        $this->assertNotNull($pending->machine_id);
        $this->assertStringContainsString('Serial Number : RICOH-DEMO-001', $pending->body_text);
        $this->assertStringContainsString('Serial No. = CANON-DEMO-002', IncomingReportEmail::where('subject', 'SAMPLE - awkward Canon labels')->firstOrFail()->body_text);
        $this->assertStringContainsString('Serial|XRX-DEMO-003', IncomingReportEmail::where('subject', 'SAMPLE - table style Xerox counters')->firstOrFail()->body_text);
        $this->assertNull($aiChallenge->machine_id);
        $this->assertStringContainsString('Serial Ref|AI-DEMO-NOMATCH-004', $aiChallenge->body_text);
        $this->assertStringContainsString('Magenta Toner|19%|LOW', $aiChallenge->body_text);
    }
}
