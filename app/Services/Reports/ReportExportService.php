<?php

namespace App\Services\Reports;

use Illuminate\Http\Response;

class ReportExportService
{
    public function csv(array $report): Response
    {
        $lines = [];
        $this->addMetadataRows($lines, $report);
        $lines[] = [];
        $lines[] = ['Date', 'Client', 'Site', 'Machine', 'Service agreement', 'Agreement start', 'Agreement end', 'Mono pages', 'Colour pages', 'Total pages', 'Included mono', 'Included colour', 'Included total', 'Chargeable mono', 'Chargeable colour', 'Chargeable total', 'Mono PPC', 'Colour PPC', 'Mono revenue', 'Colour revenue', 'Total revenue', 'Unknown usage', 'Counter reset'];

        foreach ($report['rows'] as $row) {
            $lines[] = $this->detailRow($row);
        }

        return response($this->csvString($lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$report['filename'].'.csv"',
        ]);
    }

    public function excel(array $report): Response
    {
        $xml = view('reports.exports.excel', ['report' => $report])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$report['filename'].'.xls"',
        ]);
    }

    public function pdf(array $report): Response
    {
        return response($this->simplePdf($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$report['filename'].'.pdf"',
        ]);
    }

    private function addMetadataRows(array &$lines, array $report): void
    {
        $summary = $report['summary'];

        $lines[] = ['Copier Revenue Report'];
        $lines[] = ['Scope', $report['scope_label']];
        $lines[] = ['Period', $report['period_label']];
        $lines[] = ['Date range', $report['from']->format('d/m/Y').' - '.$report['to']->format('d/m/Y')];
        $lines[] = ['Total revenue', number_format($summary['total_revenue'], 2, '.', '')];
        $lines[] = ['Mono revenue', number_format($summary['mono_revenue'], 2, '.', '')];
        $lines[] = ['Colour revenue', number_format($summary['colour_revenue'], 2, '.', '')];
        $lines[] = ['Total pages', $summary['total_pages']];
        $lines[] = ['Included pages', $summary['included_total_pages']];
        $lines[] = ['Chargeable pages', $summary['chargeable_total_pages']];
    }

    private function detailRow(array $row): array
    {
        return [
            $row['date'],
            $row['client_name'],
            $row['site_name'],
            $row['machine_name'],
            $row['service_agreement_number'] ?? 'Legacy pricing',
            $row['service_agreement_starts_on'] ?? '',
            $row['service_agreement_ends_on'] ?? '',
            $row['mono_usage'],
            $row['colour_usage'],
            $row['total_usage'],
            $row['included_mono_pages'],
            $row['included_colour_pages'],
            $row['included_total_pages'],
            $row['chargeable_mono_pages'],
            $row['chargeable_colour_pages'],
            $row['chargeable_total_pages'],
            number_format((float) $row['mono_ppc'], 3, '.', ''),
            number_format((float) $row['colour_ppc'], 3, '.', ''),
            number_format((float) $row['mono_revenue'], 2, '.', ''),
            number_format((float) $row['colour_revenue'], 2, '.', ''),
            number_format((float) $row['total_revenue'], 2, '.', ''),
            $row['usage_unknown'] ? 'Yes' : 'No',
            $row['counter_reset_detected'] ? 'Yes' : 'No',
        ];
    }

    private function csvString(array $lines): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }

    private function simplePdf(array $report): string
    {
        $summary = $report['summary'];
        $lines = [
            'Copier Revenue Report',
            'Scope: '.$report['scope_label'],
            'Period: '.$report['period_label'],
            'Range: '.$report['from']->format('d/m/Y').' - '.$report['to']->format('d/m/Y'),
            'Total revenue: GBP '.number_format($summary['total_revenue'], 2),
            'Mono revenue: GBP '.number_format($summary['mono_revenue'], 2),
            'Colour revenue: GBP '.number_format($summary['colour_revenue'], 2),
            'Total pages: '.number_format($summary['total_pages']),
            'Included pages: '.number_format($summary['included_total_pages']),
            'Chargeable pages: '.number_format($summary['chargeable_total_pages']),
            '',
            'Top revenue lines',
        ];

        $report['rows']->sortByDesc('total_revenue')->take(24)->each(function (array $row) use (&$lines) {
            $lines[] = sprintf(
                '%s | %s | %s | %s pages | GBP %s',
                $row['date'],
                $row['machine_name'],
                $row['site_name'],
                number_format((int) $row['chargeable_total_pages']),
                number_format((float) $row['total_revenue'], 2),
            );
        });

        return $this->pdfFromLines($lines);
    }

    private function pdfFromLines(array $lines): string
    {
        $content = "BT\n/F1 18 Tf\n50 790 Td\n(Copier Revenue Report) Tj\n/F1 10 Tf\n0 -28 Td\n";

        foreach (array_slice($lines, 1, 44) as $line) {
            $content .= '('.$this->escapePdfText($line).") Tj\n0 -15 Td\n";
        }

        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_substr($text, 0, 112));
    }
}
