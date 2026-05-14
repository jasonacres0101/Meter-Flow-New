<?php

namespace Tests\Feature;

use App\Models\BillingInvoice;
use App\Models\BillingSetting;
use App\Models\BillingSnapshot;
use App\Models\Company;
use App\Models\EngineerSkillProfile;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_engineer_can_update_skill_profile_and_supported_manufacturers(): void
    {
        $engineer = User::factory()->create(['company_id' => null, 'role' => User::ROLE_ENGINEER]);
        $sharp = Manufacturer::findOrCreateByName('Sharp');
        $ricoh = Manufacturer::findOrCreateByName('Ricoh');

        $this->actingAs($engineer)->put(route('profile.update'), [
            'name' => $engineer->name,
            'email' => $engineer->email,
            'skills' => [
                'networking_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'vlan_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dhcp_static_ip_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'dns_level' => EngineerSkillProfile::LEVEL_BASIC,
                'routing_level' => EngineerSkillProfile::LEVEL_BASIC,
                'firewall_level' => EngineerSkillProfile::LEVEL_ADVANCED,
                'notes' => 'Covers VLAN printer deployments.',
            ],
            'manufacturer_skills' => [
                $sharp->id => EngineerSkillProfile::LEVEL_ADVANCED,
                $ricoh->id => '',
            ],
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('engineer_skill_profiles', [
            'user_id' => $engineer->id,
            'networking_level' => EngineerSkillProfile::LEVEL_ADVANCED,
            'vlan_level' => EngineerSkillProfile::LEVEL_ADVANCED,
            'notes' => 'Covers VLAN printer deployments.',
        ]);
        $this->assertDatabaseHas('engineer_manufacturer', [
            'user_id' => $engineer->id,
            'manufacturer_id' => $sharp->id,
            'skill_level' => EngineerSkillProfile::LEVEL_ADVANCED,
        ]);
        $this->assertDatabaseMissing('engineer_manufacturer', [
            'user_id' => $engineer->id,
            'manufacturer_id' => $ricoh->id,
        ]);
    }

    public function test_company_admin_cannot_view_platform_billing_page(): void
    {
        $company = Company::factory()->create(['billing_email' => 'billing@example.com']);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->get(route('billing.show'))->assertForbidden();
    }

    public function test_company_admin_can_view_own_company_billing_page(): void
    {
        $company = Company::factory()->create([
            'billing_email' => 'billing@example.com',
            'monthly_machine_rate_override' => 8.50,
            'gocardless_mandate_id' => 'MD123',
            'gocardless_mandate_status' => 'fulfilled',
            'gocardless_mandate_confirmed_at' => now(),
        ]);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        BillingSetting::current()->update(['monthly_machine_rate' => 6.00, 'currency' => 'GBP']);
        $snapshot = BillingSnapshot::create([
            'company_id' => $company->id,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'snapshot_date' => '2026-05-25',
            'active_machine_count' => 4,
            'monthly_machine_rate' => 8.50,
            'currency' => 'GBP',
        ]);
        BillingInvoice::create([
            'company_id' => $company->id,
            'billing_snapshot_id' => $snapshot->id,
            'invoice_number' => 'INV-202605-0001',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'invoice_date' => '2026-05-31',
            'due_date' => '2026-06-14',
            'active_machine_count' => 4,
            'monthly_machine_rate' => 8.50,
            'subtotal' => 34.00,
            'tax_total' => 0,
            'total' => 34.00,
            'currency' => 'GBP',
            'status' => BillingInvoice::STATUS_ISSUED,
            'gocardless_payment_id' => 'PM123',
            'gocardless_payment_status' => 'pending_submission',
        ]);

        $this->actingAs($admin)->get(route('company-billing.show'))
            ->assertOk()
            ->assertSee('Account billing')
            ->assertSee('INV-202605-0001')
            ->assertSee('MD123')
            ->assertSee('PM123')
            ->assertDontSee('GoCardless payment settings');
    }

    public function test_company_admin_can_download_own_invoice_pdf(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Copier Services']);
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $snapshot = BillingSnapshot::create([
            'company_id' => $company->id,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'snapshot_date' => '2026-05-25',
            'active_machine_count' => 2,
            'monthly_machine_rate' => 5.00,
            'currency' => 'GBP',
        ]);
        $invoice = BillingInvoice::create([
            'company_id' => $company->id,
            'billing_snapshot_id' => $snapshot->id,
            'invoice_number' => 'INV-202605-0002',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'invoice_date' => '2026-05-31',
            'due_date' => '2026-06-14',
            'active_machine_count' => 2,
            'monthly_machine_rate' => 5.00,
            'subtotal' => 10.00,
            'tax_total' => 0,
            'total' => 10.00,
            'currency' => 'GBP',
            'status' => BillingInvoice::STATUS_ISSUED,
        ]);

        $this->actingAs($admin)->get(route('billing.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="INV-202605-0002.pdf"')
            ->assertSee('%PDF-1.4', false);
    }

    public function test_invoice_pdf_is_scoped_to_company_or_platform_admin(): void
    {
        $ownerCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $otherAdmin = User::factory()->for($otherCompany)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $platformAdmin = User::factory()->create(['company_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $snapshot = BillingSnapshot::create([
            'company_id' => $ownerCompany->id,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'snapshot_date' => '2026-05-25',
            'active_machine_count' => 1,
            'monthly_machine_rate' => 5.00,
            'currency' => 'GBP',
        ]);
        $invoice = BillingInvoice::create([
            'company_id' => $ownerCompany->id,
            'billing_snapshot_id' => $snapshot->id,
            'invoice_number' => 'INV-202605-0003',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'invoice_date' => '2026-05-31',
            'due_date' => '2026-06-14',
            'active_machine_count' => 1,
            'monthly_machine_rate' => 5.00,
            'subtotal' => 5.00,
            'tax_total' => 0,
            'total' => 5.00,
            'currency' => 'GBP',
            'status' => BillingInvoice::STATUS_ISSUED,
        ]);

        $this->actingAs($otherAdmin)->get(route('billing.invoices.pdf', $invoice))->assertForbidden();
        $this->actingAs($platformAdmin)->get(route('billing.invoices.pdf', $invoice))->assertOk();
    }

    public function test_regular_company_user_cannot_view_billing_page(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_USER]);

        $this->actingAs($user)->get(route('billing.show'))->assertForbidden();
        $this->actingAs($user)->get(route('company-billing.show'))->assertForbidden();
    }
}
