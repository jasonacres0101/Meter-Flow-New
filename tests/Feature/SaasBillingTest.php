<?php

namespace Tests\Feature;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\BillingSnapshot;
use App\Models\Client;
use App\Models\Company;
use App\Models\Machine;
use App\Models\MachineModel;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SaasBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_update_billing_settings(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->put(route('billing.update'), [
            'monthly_machine_rate' => '7.50',
            'currency' => 'gbp',
            'snapshot_day' => 25,
            'payment_terms_days' => 30,
            'gocardless_enabled' => '1',
            'gocardless_environment' => 'sandbox',
            'gocardless_access_token' => 'sandbox-token',
            'gocardless_webhook_secret' => 'webhook-secret',
            'gocardless_creditor_id' => 'CR123',
        ])->assertRedirect(route('billing.show'));

        $setting = BillingSetting::current();
        $this->assertSame('7.50', $setting->monthly_machine_rate);
        $this->assertSame('GBP', $setting->currency);
        $this->assertSame(25, $setting->snapshot_day);
        $this->assertSame(30, $setting->payment_terms_days);
        $this->assertTrue($setting->gocardless_enabled);
        $this->assertSame('sandbox', $setting->gocardless_environment);
        $this->assertSame('sandbox-token', $setting->gocardless_access_token);
        $this->assertSame('webhook-secret', $setting->gocardless_webhook_secret);
        $this->assertSame('CR123', $setting->gocardless_creditor_id);
    }

    public function test_platform_billing_captures_machine_counts_and_generates_month_end_invoice(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['is_active' => true, 'monthly_machine_rate_override' => 9.50]);
        $client = Client::factory()->for($company)->create();
        $site = Site::factory()->for($client)->create();
        $model = MachineModel::factory()->for($company)->create();

        Machine::factory()->count(3)->for($client)->for($site)->for($model)->create(['is_active' => true]);
        Machine::factory()->for($client)->for($site)->for($model)->create(['is_active' => false]);
        BillingSetting::current()->update(['monthly_machine_rate' => 6.25, 'currency' => 'GBP']);

        $this->actingAs($admin)->post(route('billing.capture'), [
            'date' => '2026-05-25',
        ])->assertRedirect(route('billing.show'));

        $snapshot = BillingSnapshot::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('2026-05-01', $snapshot->period_start->toDateString());
        $this->assertSame('2026-05-31', $snapshot->period_end->toDateString());
        $this->assertSame('2026-05-25', $snapshot->snapshot_date->toDateString());
        $this->assertSame(3, $snapshot->active_machine_count);
        $this->assertSame('9.50', $snapshot->monthly_machine_rate);

        $this->actingAs($admin)->post(route('billing.generate'), [
            'date' => '2026-05-31',
        ])->assertRedirect(route('billing.show'));

        $invoice = BillingInvoice::where('company_id', $company->id)->firstOrFail();

        $this->assertSame($snapshot->id, $invoice->billing_snapshot_id);
        $this->assertSame(3, $invoice->active_machine_count);
        $this->assertSame('28.50', $invoice->total);
        $this->assertSame(BillingInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertSame('2026-05-31', $invoice->invoice_date->toDateString());
    }

    public function test_billing_commands_gate_by_day(): void
    {
        $this->artisan('billing:capture-machine-counts', ['--date' => '2026-05-24'])
            ->expectsOutputToContain('Skipped')
            ->assertSuccessful();

        $this->artisan('billing:generate-invoices', ['--date' => '2026-05-30'])
            ->expectsOutputToContain('Skipped')
            ->assertSuccessful();
    }

    public function test_platform_admin_can_test_gocardless_connection(): void
    {
        Http::fake([
            'api-sandbox.gocardless.com/creditors*' => Http::response([
                'creditors' => [
                    ['id' => 'CR-SANDBOX-123'],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        BillingSetting::current()->update([
            'gocardless_enabled' => true,
            'gocardless_environment' => 'sandbox',
            'gocardless_access_token' => 'sandbox-token',
        ]);

        $this->actingAs($admin)->post(route('billing.gocardless.test'))
            ->assertRedirect(route('billing.show'));

        $setting = BillingSetting::current();
        $this->assertSame('CR-SANDBOX-123', $setting->gocardless_creditor_id);
        $this->assertNotNull($setting->gocardless_last_tested_at);
        $this->assertNotNull($setting->gocardless_last_success_at);
        $this->assertNull($setting->gocardless_last_error);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sandbox-token')
            && $request->hasHeader('GoCardless-Version', '2015-07-06'));
    }

    public function test_platform_admin_can_create_gocardless_mandate_link_for_account(): void
    {
        Http::fake([
            'api-sandbox.gocardless.com/billing_requests' => Http::response([
                'billing_requests' => [
                    'id' => 'BRQ123',
                    'status' => 'pending',
                    'links' => ['customer' => 'CU123'],
                ],
            ], 201),
            'api-sandbox.gocardless.com/billing_request_flows' => Http::response([
                'billing_request_flows' => [
                    'id' => 'BRF123',
                    'authorisation_url' => 'https://pay.gocardless.com/billing/static/flow?id=BRF123',
                    'links' => ['billing_request' => 'BRQ123'],
                ],
            ], 201),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['billing_email' => 'accounts@example.com']);
        BillingSetting::current()->update([
            'gocardless_enabled' => true,
            'gocardless_environment' => 'sandbox',
            'gocardless_access_token' => 'sandbox-token',
            'currency' => 'GBP',
        ]);

        $this->actingAs($admin)->post(route('companies.gocardless.mandate', $company))
            ->assertRedirect(route('companies.show', $company));

        $company->refresh();
        $this->assertSame('BRQ123', $company->gocardless_billing_request_id);
        $this->assertSame('BRF123', $company->gocardless_billing_request_flow_id);
        $this->assertSame('https://pay.gocardless.com/billing/static/flow?id=BRF123', $company->gocardless_authorisation_url);
        $this->assertSame('pending', $company->gocardless_mandate_status);
        $this->assertNotNull($company->gocardless_mandate_requested_at);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.gocardless.com/billing_requests'
            && $request['billing_requests']['mandate_request']['scheme'] === 'bacs'
            && $request['billing_requests']['mandate_request']['currency'] === 'GBP');
    }

    public function test_platform_admin_can_refresh_gocardless_mandate_status(): void
    {
        Http::fake([
            'api-sandbox.gocardless.com/billing_requests/BRQ123' => Http::response([
                'billing_requests' => [
                    'id' => 'BRQ123',
                    'status' => 'fulfilled',
                    'links' => [
                        'customer' => 'CU123',
                        'mandate' => 'MD123',
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['gocardless_billing_request_id' => 'BRQ123']);
        BillingSetting::current()->update([
            'gocardless_enabled' => true,
            'gocardless_environment' => 'sandbox',
            'gocardless_access_token' => 'sandbox-token',
        ]);

        $this->actingAs($admin)->post(route('companies.gocardless.refresh', $company))
            ->assertRedirect(route('companies.show', $company));

        $company->refresh();
        $this->assertSame('CU123', $company->gocardless_customer_id);
        $this->assertSame('MD123', $company->gocardless_mandate_id);
        $this->assertSame('fulfilled', $company->gocardless_mandate_status);
        $this->assertNotNull($company->gocardless_mandate_confirmed_at);
    }

    public function test_platform_admin_can_collect_invoice_by_gocardless_mandate(): void
    {
        Http::fake([
            'api-sandbox.gocardless.com/payments' => Http::response([
                'payments' => [
                    'id' => 'PM123',
                    'status' => 'pending_submission',
                    'charge_date' => '2026-06-03',
                ],
            ], 201),
        ]);

        $admin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $company = Company::factory()->create(['gocardless_mandate_id' => 'MD123']);
        $snapshot = BillingSnapshot::create([
            'company_id' => $company->id,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'snapshot_date' => '2026-05-25',
            'active_machine_count' => 2,
            'monthly_machine_rate' => 9.99,
            'currency' => 'GBP',
        ]);
        $invoice = BillingInvoice::create([
            'company_id' => $company->id,
            'billing_snapshot_id' => $snapshot->id,
            'invoice_number' => 'INV-2026-0001',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'invoice_date' => '2026-05-31',
            'due_date' => '2026-06-14',
            'active_machine_count' => 2,
            'monthly_machine_rate' => 9.99,
            'subtotal' => 19.99,
            'tax_total' => 0,
            'total' => 19.99,
            'currency' => 'GBP',
            'status' => BillingInvoice::STATUS_ISSUED,
        ]);
        BillingSetting::current()->update([
            'gocardless_enabled' => true,
            'gocardless_environment' => 'sandbox',
            'gocardless_access_token' => 'sandbox-token',
        ]);

        $this->actingAs($admin)->post(route('billing.invoices.collect', $invoice))
            ->assertRedirect(route('billing.show'));

        $invoice->refresh();
        $this->assertSame('PM123', $invoice->gocardless_payment_id);
        $this->assertSame('pending_submission', $invoice->gocardless_payment_status);
        $this->assertSame('2026-06-03', $invoice->gocardless_charge_date->toDateString());
        $this->assertNotNull($invoice->gocardless_payment_requested_at);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.gocardless.com/payments'
            && $request['payments']['amount'] === 1999
            && $request['payments']['links']['mandate'] === 'MD123');
    }
}
