<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailSource;
use App\Models\User;
use App\Services\Pop3MailboxClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailSourceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_email_source_for_own_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('email-sources.store'), [
            'name' => 'Reports Gmail',
            'provider' => EmailSource::PROVIDER_GMAIL,
            'mailbox_email' => 'reports@example.com',
            'username' => 'reports@example.com',
            'password' => 'app-password',
            'folder' => 'INBOX',
            'mark_as_seen' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('email-sources.index'));

        $this->assertDatabaseHas('email_sources', [
            'company_id' => $company->id,
            'name' => 'Reports Gmail',
            'provider' => EmailSource::PROVIDER_GMAIL,
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
        ]);
    }

    public function test_company_admin_can_deactivate_email_source(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create(['is_active' => true]);

        $this->actingAs($admin)->put(route('email-sources.update', $source), [
            'name' => $source->name,
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'delivery_method' => 'pop3',
            'mailbox_email' => $source->mailbox_email,
            'username' => $source->username,
            'password' => '',
            'imap_host' => $source->imap_host,
            'imap_port' => $source->imap_port,
            'encryption' => $source->encryption,
            'folder' => $source->folder,
            'mark_as_seen' => '0',
            'delete_after_ingest' => '0',
            'is_active' => '0',
            'configuration' => '{}',
        ])->assertRedirect(route('email-sources.show', $source));

        $source->refresh();

        $this->assertFalse($source->is_active);
        $this->assertFalse($source->mark_as_seen);
    }

    public function test_platform_admin_can_create_master_email_source(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->post(route('email-sources.store'), [
            'company_id' => '',
            'name' => 'Master Sample Reports',
            'provider' => EmailSource::PROVIDER_GMAIL,
            'mailbox_email' => 'master-reports@example.com',
            'username' => 'master-reports@example.com',
            'password' => 'app-password',
            'folder' => 'INBOX',
            'mark_as_seen' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('email-sources.index'));

        $this->assertDatabaseHas('email_sources', [
            'company_id' => null,
            'name' => 'Master Sample Reports',
            'mailbox_email' => 'master-reports@example.com',
        ]);
    }

    public function test_company_user_cannot_see_another_company_email_source(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        $source = EmailSource::factory()->for($otherCompany)->create();

        $this->actingAs($user)->get(route('email-sources.show', $source))->assertForbidden();
    }

    public function test_office_365_source_uses_modern_auth_fields(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('email-sources.store'), [
            'name' => 'Office Reports',
            'provider' => EmailSource::PROVIDER_OFFICE365,
            'mailbox_email' => 'reports@example.com',
            'oauth_tenant_id' => 'tenant-id',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'folder' => 'Inbox',
            'mark_as_seen' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('email-sources.index'));

        $source = EmailSource::where('name', 'Office Reports')->firstOrFail();

        $this->assertTrue($source->usesMicrosoftGraph());
        $this->assertSame(EmailSource::AUTH_MICROSOFT_GRAPH, $source->auth_type);
        $this->assertNull($source->imap_host);
        $this->assertNull($source->password);
        $this->assertSame('https://graph.microsoft.com/.default', $source->oauth_scope);
    }

    public function test_office_365_email_source_has_test_connection_action(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token'], 200),
            'graph.microsoft.com/*' => Http::response(['value' => []], 200),
        ]);

        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_OFFICE365,
            'auth_type' => EmailSource::AUTH_MICROSOFT_GRAPH,
            'mailbox_email' => 'reports@example.com',
            'oauth_tenant_id' => 'tenant-id',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'oauth_scope' => 'https://graph.microsoft.com/.default',
            'folder' => 'Inbox',
        ]);

        $this->actingAs($admin)->post(route('email-sources.test', $source))
            ->assertRedirect();

        $source->refresh();
        $this->assertNotNull($source->last_checked_at);
        $this->assertNotNull($source->last_success_at);
        $this->assertSame('connected', $source->oauth_status);
    }

    public function test_failed_email_source_test_shows_feedback_on_index_page(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'name' => 'Broken reports inbox',
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'imap_host' => null,
            'username' => null,
            'password' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('email-sources.index'))
            ->post(route('email-sources.test', $source))
            ->assertRedirect(route('email-sources.index'));

        $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('email-sources.index'))
            ->post(route('email-sources.test', $source))
            ->assertSee('Email source test failed');

        $source->refresh();
        $this->assertNotNull($source->last_checked_at);
        $this->assertNotNull($source->last_error);
    }

    public function test_imap_source_test_checks_configuration_when_php_imap_extension_is_unavailable(): void
    {
        if (function_exists('imap_open')) {
            $this->markTestSkipped('This check only applies when the PHP IMAP extension is unavailable.');
        }

        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_GMAIL,
            'auth_type' => EmailSource::AUTH_BASIC,
            'mailbox_email' => 'reports@example.com',
            'username' => 'reports@example.com',
            'password' => 'app-password',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'encryption' => 'ssl',
            'folder' => 'INBOX',
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->post(route('email-sources.test', $source))
            ->assertOk()
            ->assertSee('Email source settings look valid')
            ->assertSee('Live IMAP testing is unavailable');

        $source->refresh();
        $this->assertNotNull($source->last_checked_at);
        $this->assertNull($source->last_success_at);
        $this->assertNull($source->last_error);
    }

    public function test_custom_email_source_can_use_webhook_delivery(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('email-sources.store'), [
            'name' => 'Inbound API',
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'delivery_method' => 'webhook',
            'webhook_provider' => 'sendgrid',
            'webhook_secret' => 'source-secret',
            'mailbox_email' => 'reports@example.com',
            'folder' => 'INBOX',
            'is_active' => '1',
        ])->assertRedirect(route('email-sources.index'));

        $source = EmailSource::where('name', 'Inbound API')->firstOrFail();

        $this->assertTrue($source->usesWebhookDelivery());
        $this->assertSame('sendgrid', $source->webhookProvider());
        $this->assertSame('source-secret', $source->password);
        $this->assertNull($source->imap_host);
        $this->assertNull($source->username);
    }

    public function test_custom_email_source_can_use_pop_delivery(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);

        $this->actingAs($admin)->post(route('email-sources.store'), [
            'name' => 'POP mailbox',
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'delivery_method' => 'pop3',
            'mailbox_email' => 'reports@example.com',
            'username' => 'reports@example.com',
            'password' => 'mailbox-password',
            'imap_host' => 'pop.example.com',
            'imap_port' => 995,
            'encryption' => 'ssl',
            'folder' => 'INBOX',
            'is_active' => '1',
        ])->assertRedirect(route('email-sources.index'));

        $source = EmailSource::where('name', 'POP mailbox')->firstOrFail();

        $this->assertSame(EmailSource::AUTH_BASIC, $source->auth_type);
        $this->assertSame('pop3', $source->mailboxProtocol());
        $this->assertStringContainsString('/pop3/ssl', $source->mailboxString());
    }

    public function test_pop_source_test_checks_live_pop_connection(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'mailbox_email' => 'reports@example.com',
            'username' => 'reports@example.com',
            'password' => 'mailbox-password',
            'imap_host' => 'pop.example.com',
            'imap_port' => 995,
            'encryption' => 'ssl',
            'folder' => 'INBOX',
            'configuration' => ['mailbox_protocol' => 'pop3'],
        ]);

        $this->mock(Pop3MailboxClient::class, function ($mock) use ($source) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->withArgs(fn (EmailSource $testedSource) => $testedSource->is($source));
        });

        $this->followingRedirects()
            ->actingAs($admin)
            ->post(route('email-sources.test', $source))
            ->assertOk()
            ->assertSee('Email source test succeeded.');

        $source->refresh();
        $this->assertNotNull($source->last_checked_at);
        $this->assertNotNull($source->last_success_at);
        $this->assertNull($source->last_error);
    }

    public function test_imap_poll_records_missing_php_imap_extension_on_source(): void
    {
        if (function_exists('imap_open')) {
            $this->markTestSkipped('This check only applies when the PHP IMAP extension is unavailable.');
        }

        $source = EmailSource::factory()->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'configuration' => ['mailbox_protocol' => 'imap'],
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'encryption' => 'ssl',
        ]);

        $exitCode = Artisan::call('reports:poll-imap', ['--source' => $source->id]);

        $source->refresh();

        $this->assertSame(1, $exitCode);
        $this->assertNotNull($source->last_checked_at);
        $this->assertNull($source->last_success_at);
        $this->assertStringContainsString('PHP IMAP extension is not installed', $source->last_error);
    }

    public function test_pop_client_parses_raw_report_email(): void
    {
        $raw = "From: Copier <copier@example.test>\r\n"
            ."To: Reports <reports@example.com>\r\n"
            ."Subject: =?UTF-8?Q?MX-2630N_Status_Report?=\r\n"
            ."Date: Wed, 13 May 2026 10:15:00 +0000\r\n"
            ."Content-Type: text/plain; charset=UTF-8\r\n"
            ."Content-Transfer-Encoding: quoted-printable\r\n"
            ."\r\n"
            ."Serial Number : POP-001\r\nTotal Counter : 12345";

        $message = app(Pop3MailboxClient::class)->parseMessage($raw);

        $this->assertSame('copier@example.test', $message['from_email']);
        $this->assertSame('reports@example.com', $message['to_email']);
        $this->assertSame('MX-2630N Status Report', $message['subject']);
        $this->assertStringContainsString('Serial Number : POP-001', $message['body_text']);
    }

    public function test_webhook_email_source_test_checks_configuration_without_live_connection(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_WEBHOOK,
            'mailbox_email' => 'reports@example.com',
            'password' => 'source-secret',
            'configuration' => ['webhook_provider' => 'generic'],
            'imap_host' => null,
            'imap_port' => null,
            'encryption' => null,
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->post(route('email-sources.test', $source))
            ->assertOk()
            ->assertSee('Webhook email source is ready');

        $source->refresh();
        $this->assertNotNull($source->last_checked_at);
        $this->assertNull($source->last_success_at);
        $this->assertNull($source->last_error);
    }

    public function test_inbound_webhook_token_links_email_to_source_company(): void
    {
        $company = Company::factory()->create();
        $source = EmailSource::factory()->for($company)->create([
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_WEBHOOK,
            'mailbox_email' => 'reports@example.com',
            'password' => 'source-secret',
            'configuration' => ['webhook_provider' => 'sendgrid'],
            'imap_host' => null,
            'imap_port' => null,
            'encryption' => null,
        ]);

        $this->withHeader('X-Email-Source-Token', 'source-secret')
            ->postJson(route('inbound.sendgrid'), [
                'from_email' => 'machine@example.test',
                'to_email' => 'Reports <reports@example.com>',
                'subject' => 'Status report',
                'text' => 'Serial Number : WEBHOOK-001',
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('incoming_report_emails', [
            'company_id' => $company->id,
            'to_email' => 'Reports <reports@example.com>',
            'subject' => 'Status report',
        ]);

        $source->refresh();
        $this->assertNotNull($source->last_success_at);
    }
}
