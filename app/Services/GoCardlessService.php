<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\Company;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class GoCardlessService
{
    /**
     * @throws RequestException
     */
    public function testConnection(BillingSetting $setting): array
    {
        $response = Http::withToken($setting->gocardless_access_token)
            ->acceptJson()
            ->withHeaders(['GoCardless-Version' => '2015-07-06'])
            ->get($setting->gocardlessBaseUrl().'/creditors', ['limit' => 1])
            ->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     */
    public function createMandateFlow(BillingSetting $setting, Company $company): array
    {
        $billingRequest = $this->client($setting)
            ->post($setting->gocardlessBaseUrl().'/billing_requests', [
                'billing_requests' => [
                    'mandate_request' => [
                        'currency' => $setting->currency,
                        'scheme' => 'bacs',
                    ],
                    'metadata' => [
                        'company_id' => (string) $company->id,
                        'account_reference' => (string) $company->account_reference,
                    ],
                ],
            ])
            ->throw()
            ->json('billing_requests');

        $flow = $this->client($setting)
            ->post($setting->gocardlessBaseUrl().'/billing_request_flows', [
                'billing_request_flows' => [
                    'redirect_uri' => URL::route('gocardless.mandate.return', $company),
                    'exit_uri' => URL::route('companies.show', $company),
                    'links' => [
                        'billing_request' => data_get($billingRequest, 'id'),
                    ],
                ],
            ])
            ->throw()
            ->json('billing_request_flows');

        return [
            'billing_request' => $billingRequest,
            'flow' => $flow,
        ];
    }

    /**
     * @throws RequestException
     */
    public function getBillingRequest(BillingSetting $setting, string $billingRequestId): array
    {
        return $this->client($setting)
            ->get($setting->gocardlessBaseUrl().'/billing_requests/'.$billingRequestId)
            ->throw()
            ->json('billing_requests');
    }

    /**
     * @throws RequestException
     */
    public function createPayment(BillingSetting $setting, BillingInvoice $invoice): array
    {
        $invoice->loadMissing('company');

        return $this->client($setting)
            ->post($setting->gocardlessBaseUrl().'/payments', [
                'payments' => [
                    'amount' => (int) round(((float) $invoice->total) * 100),
                    'currency' => $invoice->currency,
                    'description' => 'Copier monitoring SaaS invoice '.$invoice->invoice_number,
                    'reference' => $invoice->invoice_number,
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'company_id' => (string) $invoice->company_id,
                    ],
                    'links' => [
                        'mandate' => $invoice->company->gocardless_mandate_id,
                    ],
                ],
            ])
            ->throw()
            ->json('payments');
    }

    private function client(BillingSetting $setting)
    {
        return Http::withToken($setting->gocardless_access_token)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['GoCardless-Version' => '2015-07-06']);
    }
}
