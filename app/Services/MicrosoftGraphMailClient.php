<?php

namespace App\Services;

use App\Models\EmailSource;
use App\Models\PlatformMailSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class MicrosoftGraphMailClient
{
    /**
     * @throws RequestException
     */
    public function testMailbox(EmailSource $source): void
    {
        $token = $this->accessToken(
            $source->oauth_tenant_id,
            $source->oauth_client_id,
            $source->oauth_client_secret,
            $source->oauth_scope ?: 'https://graph.microsoft.com/.default'
        );

        Http::withToken($token)
            ->acceptJson()
            ->get(sprintf(
                'https://graph.microsoft.com/v1.0/users/%s/mailFolders/%s/messages',
                rawurlencode($source->mailbox_email),
                rawurlencode($source->folder ?: 'Inbox')
            ), ['$top' => 1])
            ->throw();
    }

    /**
     * @throws RequestException
     */
    public function sendFromPlatform(PlatformMailSetting $setting, string $toEmail, string $subject, string $htmlBody, ?string $textBody = null): void
    {
        $token = $this->accessToken(
            $setting->oauth_tenant_id,
            $setting->oauth_client_id,
            $setting->oauth_client_secret,
            $setting->oauth_scope ?: 'https://graph.microsoft.com/.default'
        );

        Http::withToken($token)
            ->acceptJson()
            ->post(sprintf('https://graph.microsoft.com/v1.0/users/%s/sendMail', rawurlencode($setting->from_email)), [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $htmlBody ?: nl2br(e($textBody ?? '')),
                    ],
                    'toRecipients' => [
                        ['emailAddress' => ['address' => $toEmail]],
                    ],
                    'from' => [
                        'emailAddress' => [
                            'name' => $setting->from_name,
                            'address' => $setting->from_email,
                        ],
                    ],
                ],
                'saveToSentItems' => true,
            ])
            ->throw();
    }

    /**
     * @throws RequestException
     */
    private function accessToken(string $tenantId, string $clientId, string $clientSecret, string $scope): string
    {
        return Http::asForm()
            ->acceptJson()
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json('access_token');
    }
}
