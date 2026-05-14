<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmailSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSource>
 */
class EmailSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Reports mailbox',
            'provider' => EmailSource::PROVIDER_CUSTOM_IMAP,
            'auth_type' => EmailSource::AUTH_BASIC,
            'mailbox_email' => fake()->safeEmail(),
            'username' => fake()->safeEmail(),
            'password' => 'password',
            'imap_host' => 'imap.example.test',
            'imap_port' => 993,
            'encryption' => 'ssl',
            'folder' => 'INBOX',
            'mark_as_seen' => true,
            'delete_after_ingest' => false,
            'configuration' => [],
            'is_active' => true,
        ];
    }
}
