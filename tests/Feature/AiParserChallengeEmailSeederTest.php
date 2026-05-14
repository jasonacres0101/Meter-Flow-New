<?php

namespace Tests\Feature;

use App\Models\IncomingReportEmail;
use Database\Seeders\AiParserChallengeEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiParserChallengeEmailSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_ai_parser_challenge_emails_without_running_full_demo_seed(): void
    {
        $this->seed(AiParserChallengeEmailSeeder::class);

        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'AI TEST - matched pipe toner report',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'AI TEST - matched bracket comma report',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'AI TEST - matched table layout report',
            'parse_status' => IncomingReportEmail::STATUS_PENDING_TEMPLATE,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'AI TEST - unmatched pipe toner report',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);
        $this->assertDatabaseHas('incoming_report_emails', [
            'subject' => 'AI TEST - unmatched csv counter report',
            'parse_status' => IncomingReportEmail::STATUS_UNMATCHED,
        ]);

        $matched = IncomingReportEmail::where('subject', 'AI TEST - matched pipe toner report')->firstOrFail();
        $unmatched = IncomingReportEmail::where('subject', 'AI TEST - unmatched pipe toner report')->firstOrFail();

        $this->assertNotNull($matched->machine_id);
        $this->assertNull($unmatched->machine_id);
        $this->assertStringContainsString('Magenta Toner|19%|LOW', $matched->body_text);
        $this->assertStringContainsString('Serial Ref|AI-DEMO-NOMATCH-004', $unmatched->body_text);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(AiParserChallengeEmailSeeder::class);
        $this->seed(AiParserChallengeEmailSeeder::class);

        $this->assertSame(5, IncomingReportEmail::where('subject', 'like', 'AI TEST -%')->count());
    }
}
