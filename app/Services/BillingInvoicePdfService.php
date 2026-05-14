<?php

namespace App\Services;

use App\Models\BillingInvoice;

class BillingInvoicePdfService
{
    public function make(BillingInvoice $invoice): string
    {
        $invoice->loadMissing('company');

        return $this->designedPdf($invoice);
    }

    /**
     * @return array<int, string>
     */
    private function addressLines(BillingInvoice $invoice): array
    {
        return collect([
            $invoice->company->address_line_1,
            $invoice->company->address_line_2,
            $invoice->company->city,
            $invoice->company->county,
            $invoice->company->postcode,
            $invoice->company->country,
        ])->filter()->values()->all();
    }

    private function designedPdf(BillingInvoice $invoice): string
    {
        $content = '';
        $ink = [15, 23, 42];
        $muted = [100, 116, 139];
        $teal = [13, 148, 136];
        $softTeal = [204, 251, 241];
        $slate = [241, 245, 249];
        $border = [203, 213, 225];

        $this->fillRect($content, 0, 742, 595, 100, [15, 23, 42]);
        $this->fillRect($content, 42, 774, 42, 42, $teal);
        $this->text($content, 54, 789, 'CM', 15, 'F2', [255, 255, 255]);
        $this->text($content, 100, 803, 'Copier Monitor', 22, 'F2', [255, 255, 255]);
        $this->text($content, 101, 784, 'Managed print SaaS billing', 10, 'F1', [203, 213, 225]);
        $this->text($content, 430, 802, 'INVOICE', 28, 'F2', [255, 255, 255]);
        $this->text($content, 432, 780, $invoice->invoice_number, 10, 'F1', [203, 213, 225]);

        $this->fillRect($content, 42, 665, 245, 56, $slate);
        $this->fillRect($content, 307, 665, 246, 56, $softTeal);
        $this->text($content, 58, 700, 'Invoice date', 8, 'F2', $muted);
        $this->text($content, 58, 682, $invoice->invoice_date->format('d M Y'), 14, 'F2', $ink);
        $this->text($content, 195, 700, 'Due date', 8, 'F2', $muted);
        $this->text($content, 195, 682, $invoice->due_date?->format('d M Y') ?? 'Not set', 14, 'F2', $ink);
        $this->text($content, 324, 700, 'Total due', 8, 'F2', [15, 118, 110]);
        $this->text($content, 324, 680, $this->money($invoice, (float) $invoice->total), 20, 'F2', [15, 118, 110]);
        $this->text($content, 468, 700, 'Status', 8, 'F2', [15, 118, 110]);
        $this->text($content, 468, 682, ucfirst($invoice->status), 13, 'F2', [15, 118, 110]);

        $this->strokeRect($content, 42, 495, 245, 140, $border);
        $this->strokeRect($content, 307, 495, 246, 140, $border);
        $this->text($content, 58, 612, 'Bill to', 10, 'F2', $teal);
        $this->text($content, 58, 592, $invoice->company->name, 14, 'F2', $ink);
        $y = 575;
        foreach (array_filter([$invoice->company->billing_email, ...$this->addressLines($invoice)]) as $line) {
            $this->text($content, 58, $y, $line, 9, 'F1', $muted);
            $y -= 14;
        }

        $this->text($content, 323, 612, 'Billing details', 10, 'F2', $teal);
        $this->labelValue($content, 323, 592, 'Billing period', $invoice->period_start->format('d M Y').' - '.$invoice->period_end->format('d M Y'));
        $this->labelValue($content, 323, 566, 'Active machines', (string) $invoice->active_machine_count);
        $this->labelValue($content, 323, 540, 'Price per machine', $this->money($invoice, (float) $invoice->monthly_machine_rate));
        $this->labelValue($content, 323, 514, 'Payment method', $invoice->company->gocardless_mandate_id ? 'Direct Debit' : 'Pending setup');

        $this->fillRect($content, 42, 445, 511, 30, [30, 41, 59]);
        $this->text($content, 58, 456, 'Description', 9, 'F2', [255, 255, 255]);
        $this->text($content, 330, 456, 'Qty', 9, 'F2', [255, 255, 255]);
        $this->text($content, 390, 456, 'Unit', 9, 'F2', [255, 255, 255]);
        $this->text($content, 488, 456, 'Amount', 9, 'F2', [255, 255, 255]);

        $this->strokeRect($content, 42, 365, 511, 80, $border);
        $this->text($content, 58, 418, 'Monthly SaaS platform subscription', 11, 'F2', $ink);
        $this->text($content, 58, 400, 'Machine count captured for '.$invoice->period_end->format('F Y'), 9, 'F1', $muted);
        $this->text($content, 332, 410, (string) $invoice->active_machine_count, 10, 'F1', $ink);
        $this->text($content, 390, 410, $this->money($invoice, (float) $invoice->monthly_machine_rate), 10, 'F1', $ink);
        $this->text($content, 482, 410, $this->money($invoice, (float) $invoice->subtotal), 10, 'F2', $ink);

        $this->fillRect($content, 340, 250, 213, 88, $slate);
        $this->summaryLine($content, 358, 314, 'Subtotal', $this->money($invoice, (float) $invoice->subtotal));
        $this->summaryLine($content, 358, 290, 'Tax', $this->money($invoice, (float) $invoice->tax_total));
        $this->fillRect($content, 340, 250, 213, 34, $teal);
        $this->text($content, 358, 262, 'Total due', 11, 'F2', [255, 255, 255]);
        $this->text($content, 470, 262, $this->money($invoice, (float) $invoice->total), 13, 'F2', [255, 255, 255]);

        $this->strokeRect($content, 42, 250, 268, 88, $border);
        $this->text($content, 58, 314, 'Payment status', 10, 'F2', $teal);
        $payment = $invoice->gocardless_payment_id
            ? 'GoCardless '.$invoice->gocardless_payment_id.' / '.($invoice->gocardless_payment_status ?: 'requested')
            : 'Payment collection has not been requested yet.';
        $this->text($content, 58, 292, $payment, 9, 'F1', $ink);
        if ($invoice->gocardless_charge_date) {
            $this->text($content, 58, 274, 'Charge date: '.$invoice->gocardless_charge_date->format('d M Y'), 9, 'F1', $muted);
        }

        $this->line($content, 42, 130, 553, 130, $border);
        $this->text($content, 42, 108, 'Thank you for using Copier Monitor.', 11, 'F2', $ink);
        $this->text($content, 42, 90, 'For billing questions, contact your SaaS account support team.', 9, 'F1', $muted);
        $this->text($content, 420, 90, 'Generated '.now()->format('d M Y H:i'), 8, 'F1', $muted);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
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

    private function money(BillingInvoice $invoice, float $amount): string
    {
        return $invoice->currency.' '.number_format($amount, 2);
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function text(string &$content, int $x, int $y, string $text, int $size = 10, string $font = 'F1', array $rgb = [15, 23, 42]): void
    {
        $content .= sprintf(
            "BT\n%.3f %.3f %.3f rg\n/%s %d Tf\n%d %d Td\n(%s) Tj\nET\n",
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $font,
            $size,
            $x,
            $y,
            $this->escapePdfText($text),
        );
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function fillRect(string &$content, int $x, int $y, int $width, int $height, array $rgb): void
    {
        $content .= sprintf("%.3f %.3f %.3f rg\n%d %d %d %d re\nf\n", $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x, $y, $width, $height);
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function strokeRect(string &$content, int $x, int $y, int $width, int $height, array $rgb): void
    {
        $content .= sprintf("%.3f %.3f %.3f RG\n%d %d %d %d re\nS\n", $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x, $y, $width, $height);
    }

    /**
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function line(string &$content, int $x1, int $y1, int $x2, int $y2, array $rgb): void
    {
        $content .= sprintf("%.3f %.3f %.3f RG\n%d %d m\n%d %d l\nS\n", $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $x1, $y1, $x2, $y2);
    }

    private function labelValue(string &$content, int $x, int $y, string $label, string $value): void
    {
        $this->text($content, $x, $y, $label, 8, 'F2', [100, 116, 139]);
        $this->text($content, $x, $y - 14, $value, 10, 'F1', [15, 23, 42]);
    }

    private function summaryLine(string &$content, int $x, int $y, string $label, string $value): void
    {
        $this->text($content, $x, $y, $label, 10, 'F1', [71, 85, 105]);
        $this->text($content, 476, $y, $value, 10, 'F2', [15, 23, 42]);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_substr($text, 0, 118));
    }
}
