<?php

namespace Tests\Unit;

use App\Services\Reports\ReportTemplateSuggestionService;
use PHPUnit\Framework\TestCase;

class ReportTemplateSuggestionServiceTest extends TestCase
{
    public function test_mapping_review_shows_clean_values_for_csv_and_pipe_rows(): void
    {
        $review = app(ReportTemplateSuggestionService::class)->reviewMapping(<<<'TEXT'
Field,Value,Comment
Serial Number,AI-DEMO-CSV-005,No machine should match
Report Date,2026-05-14 16:00:00,Generated sample
Device Name,CSV Counter Sample,AI test
Total Pages,56890,All impressions
Black Toner,54%,OK
Waste Toner,OK,Container status
TEXT, [
            'serial_number_labels' => ['Serial Number'],
            'report_date_labels' => ['Report Date'],
            'machine_name_labels' => ['Device Name'],
            'total_counter_labels' => ['Total Pages'],
            'black_toner_percentage_labels' => ['Black Toner'],
            'waste_toner_status_labels' => ['Waste Toner'],
        ]);

        $values = collect($review['rows'])->pluck('value', 'key');

        $this->assertSame('AI-DEMO-CSV-005', $values['serial_number_labels']);
        $this->assertSame('2026-05-14 16:00:00', $values['report_date_labels']);
        $this->assertSame('CSV Counter Sample', $values['machine_name_labels']);
        $this->assertSame('56890', $values['total_counter_labels']);
        $this->assertSame('54%', $values['black_toner_percentage_labels']);
        $this->assertSame('OK', $values['waste_toner_status_labels']);
    }
}
