<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\BillingSnapshot;
use App\Models\Company;
use App\Services\GoCardlessService;
use App\Services\SaasBillingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function show(Request $request): View
    {
        return view('billing.show', [
            'setting' => BillingSetting::current(),
            'snapshots' => BillingSnapshot::with('company')->latest('period_end')->latest()->paginate(10, ['*'], 'snapshots_page'),
            'invoices' => BillingInvoice::with('company')->latest('invoice_date')->latest()->paginate(10, ['*'], 'invoices_page'),
            'currentMonthInvoicesTotal' => BillingInvoice::query()
                ->whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'monthly_machine_rate' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'snapshot_day' => ['required', 'integer', 'min:1', 'max:28'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:90'],
            'gocardless_enabled' => ['nullable', 'boolean'],
            'gocardless_environment' => ['required', 'in:sandbox,live'],
            'gocardless_access_token' => ['nullable', 'string', 'max:2000'],
            'gocardless_webhook_secret' => ['nullable', 'string', 'max:2000'],
            'gocardless_creditor_id' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = BillingSetting::current();
        $update = [
            'monthly_machine_rate' => $data['monthly_machine_rate'],
            'currency' => strtoupper($data['currency']),
            'snapshot_day' => $data['snapshot_day'],
            'payment_terms_days' => $data['payment_terms_days'],
            'gocardless_enabled' => $request->boolean('gocardless_enabled'),
            'gocardless_environment' => $data['gocardless_environment'],
            'gocardless_creditor_id' => $data['gocardless_creditor_id'] ?? null,
        ];

        if (filled($data['gocardless_access_token'] ?? null)) {
            $update['gocardless_access_token'] = $data['gocardless_access_token'];
        }

        if (filled($data['gocardless_webhook_secret'] ?? null)) {
            $update['gocardless_webhook_secret'] = $data['gocardless_webhook_secret'];
        }

        $setting->update($update);

        return redirect()->route('billing.show')->with('status', 'Billing settings updated.');
    }

    public function capture(Request $request, SaasBillingService $billing): RedirectResponse
    {
        $date = CarbonImmutable::parse($request->input('date', now()->toDateString()));
        $snapshots = $billing->captureMonthlyMachineSnapshots($date);

        return redirect()->route('billing.show')->with('status', "Captured {$snapshots->count()} account billing snapshots.");
    }

    public function generate(Request $request, SaasBillingService $billing): RedirectResponse
    {
        $date = CarbonImmutable::parse($request->input('date', now()->endOfMonth()->toDateString()));
        $invoices = $billing->generateMonthEndInvoices($date);

        return redirect()->route('billing.show')->with('status', "Generated {$invoices->count()} account billing invoices.");
    }

    public function testGoCardless(GoCardlessService $goCardless): RedirectResponse
    {
        $setting = BillingSetting::current();
        abort_unless($setting->gocardlessIsReady(), 422, 'Enable GoCardless and save an access token before testing.');

        $setting->update(['gocardless_last_tested_at' => now()]);

        try {
            $payload = $goCardless->testConnection($setting);
            $creditorId = data_get($payload, 'creditors.0.id');

            $setting->update([
                'gocardless_creditor_id' => $setting->gocardless_creditor_id ?: $creditorId,
                'gocardless_last_success_at' => now(),
                'gocardless_last_error' => null,
            ]);

            return redirect()->route('billing.show')->with('status', 'GoCardless connection test succeeded.');
        } catch (Throwable $exception) {
            $setting->update(['gocardless_last_error' => $exception->getMessage()]);

            return redirect()->route('billing.show')->withErrors(['gocardless' => 'GoCardless test failed: '.$exception->getMessage()]);
        }
    }

    public function createMandate(Company $company, GoCardlessService $goCardless): RedirectResponse
    {
        $setting = BillingSetting::current();
        abort_unless($setting->gocardlessIsReady(), 422, 'Enable GoCardless and save an access token before creating mandate links.');

        try {
            $payload = $goCardless->createMandateFlow($setting, $company);
            $billingRequest = $payload['billing_request'];
            $flow = $payload['flow'];

            $company->update([
                'gocardless_billing_request_id' => data_get($billingRequest, 'id'),
                'gocardless_billing_request_flow_id' => data_get($flow, 'id'),
                'gocardless_authorisation_url' => data_get($flow, 'authorisation_url'),
                'gocardless_mandate_status' => data_get($billingRequest, 'status', 'pending'),
                'gocardless_mandate_requested_at' => now(),
                'gocardless_mandate_confirmed_at' => null,
            ]);

            return redirect()->route('companies.show', $company)->with('status', 'GoCardless mandate authorisation link created.');
        } catch (Throwable $exception) {
            return redirect()->route('companies.show', $company)->withErrors(['gocardless' => 'Could not create mandate link: '.$exception->getMessage()]);
        }
    }

    public function refreshMandate(Company $company, GoCardlessService $goCardless): RedirectResponse
    {
        try {
            $this->refreshMandateFromGoCardless($company, $goCardless);
        } catch (Throwable $exception) {
            return redirect()->route('companies.show', $company)->withErrors(['gocardless' => 'Could not refresh mandate: '.$exception->getMessage()]);
        }

        return redirect()->route('companies.show', $company)->with('status', 'GoCardless mandate status refreshed.');
    }

    public function mandateReturn(Company $company, GoCardlessService $goCardless): View
    {
        if ($company->gocardless_billing_request_id && BillingSetting::current()->gocardlessIsReady()) {
            $this->refreshMandateFromGoCardless($company, $goCardless, false);
            $company->refresh();
        }

        return view('gocardless.mandate-return', ['company' => $company]);
    }

    public function collectInvoice(BillingInvoice $billingInvoice, GoCardlessService $goCardless): RedirectResponse
    {
        $setting = BillingSetting::current();
        abort_unless($setting->gocardlessIsReady(), 422, 'Enable GoCardless and save an access token before collecting payments.');

        $billingInvoice->load('company');
        abort_unless($billingInvoice->company->hasGoCardlessMandate(), 422, 'This account does not have a GoCardless mandate yet.');
        abort_if($billingInvoice->gocardless_payment_id, 422, 'This invoice already has a GoCardless payment request.');

        try {
            $payment = $goCardless->createPayment($setting, $billingInvoice);

            $billingInvoice->update([
                'gocardless_payment_id' => data_get($payment, 'id'),
                'gocardless_payment_status' => data_get($payment, 'status', 'pending_submission'),
                'gocardless_payment_error' => null,
                'gocardless_charge_date' => data_get($payment, 'charge_date'),
                'gocardless_payment_requested_at' => now(),
                'gocardless_payment_confirmed_at' => in_array(data_get($payment, 'status'), ['confirmed', 'paid_out'], true) ? now() : null,
            ]);

            return redirect()->route('billing.show')->with('status', 'GoCardless payment collection requested.');
        } catch (Throwable $exception) {
            $billingInvoice->update([
                'gocardless_payment_error' => $exception->getMessage(),
            ]);

            return redirect()->route('billing.show')->withErrors(['gocardless_payment' => 'Payment collection failed: '.$exception->getMessage()]);
        }
    }

    private function refreshMandateFromGoCardless(Company $company, GoCardlessService $goCardless, bool $throw = true): void
    {
        $setting = BillingSetting::current();
        abort_unless($setting->gocardlessIsReady(), 422, 'Enable GoCardless and save an access token before refreshing mandates.');
        abort_unless($company->gocardless_billing_request_id, 422, 'This account does not have a GoCardless billing request yet.');

        try {
            $billingRequest = $goCardless->getBillingRequest($setting, $company->gocardless_billing_request_id);
            $mandateId = data_get($billingRequest, 'links.mandate');

            $company->update([
                'gocardless_customer_id' => data_get($billingRequest, 'links.customer', $company->gocardless_customer_id),
                'gocardless_mandate_id' => $mandateId ?: $company->gocardless_mandate_id,
                'gocardless_mandate_status' => data_get($billingRequest, 'status', $company->gocardless_mandate_status),
                'gocardless_mandate_confirmed_at' => $mandateId ? ($company->gocardless_mandate_confirmed_at ?: now()) : $company->gocardless_mandate_confirmed_at,
            ]);
        } catch (Throwable $exception) {
            if ($throw) {
                throw $exception;
            }
        }
    }
}
