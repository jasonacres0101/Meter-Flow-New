<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Services\BillingInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BillingInvoicePdfController extends Controller
{
    public function __invoke(Request $request, BillingInvoice $billingInvoice, BillingInvoicePdfService $pdf): Response
    {
        $billingInvoice->load('company');

        $user = $request->user();
        abort_unless($user?->isPlatformAdmin() || ($user?->isCompanyAdmin() && $billingInvoice->company_id === $user->company_id), 403);

        return response($pdf->make($billingInvoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$billingInvoice->invoice_number.'.pdf"',
        ]);
    }
}
