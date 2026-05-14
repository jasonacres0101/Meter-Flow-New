<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\BillingSnapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyBillingController extends Controller
{
    public function show(Request $request): View
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        $setting = BillingSetting::current();
        $activeMachineCount = $company->machines()
            ->where('machines.is_active', true)
            ->count();
        $monthlyMachineRate = $company->monthly_machine_rate_override ?? $setting->monthly_machine_rate;
        $estimatedMonthlyTotal = round($activeMachineCount * (float) $monthlyMachineRate, 2);

        $latestInvoice = BillingInvoice::query()
            ->where('company_id', $company->id)
            ->latest('invoice_date')
            ->latest()
            ->first();

        return view('company-billing.show', [
            'company' => $company,
            'setting' => $setting,
            'activeMachineCount' => $activeMachineCount,
            'monthlyMachineRate' => $monthlyMachineRate,
            'estimatedMonthlyTotal' => $estimatedMonthlyTotal,
            'latestInvoice' => $latestInvoice,
            'invoices' => BillingInvoice::query()
                ->where('company_id', $company->id)
                ->latest('invoice_date')
                ->latest()
                ->paginate(12, ['*'], 'invoices_page'),
            'snapshots' => BillingSnapshot::query()
                ->where('company_id', $company->id)
                ->latest('period_end')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
