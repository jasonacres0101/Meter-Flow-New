<?php

namespace App\Services;

use App\Models\EmailSource;
use RuntimeException;

class Pop3MailboxClient
{
    public function testConnection(EmailSource $source): void
    {
        $connection = $this->connect($source);

        try {
            $this->command($connection, 'USER '.$source->username);
            $this->command($connection, 'PASS '.$source->password);
            $this->command($connection, 'QUIT', allowClosed: true);
        } finally {
            if (is_resource($connection)) {
                fclose($connection);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function fetch(EmailSource $source): array
    {
        $connection = $this->connect($source);

        try {
            $this->command($connection, 'USER '.$source->username);
            $this->command($connection, 'PASS '.$source->password);

            $uids = $this->uidList($connection);
            $processed = $source->configuration['processed_pop3_uids'] ?? [];
            $messages = [];

            foreach ($uids as $messageNumber => $uid) {
                if (in_array($uid, $processed, true)) {
                    continue;
                }

                $raw = $this->retrieve($connection, $messageNumber);
                $messages[] = array_merge($this->parseMessage($raw), [
                    'message_number' => $messageNumber,
                    'uid' => $uid,
                ]);

                if ($source->delete_after_ingest) {
                    $this->command($connection, 'DELE '.$messageNumber);
                }
            }

            $this->command($connection, 'QUIT', allowClosed: true);

            return $messages;
        } finally {
            if (is_resource($connection)) {
                fclose($connection);
            }
        }
    }

    /** @return array<string, string|null> */
    public function parseMessage(string $raw): array
    {
        [$rawHeaders, $body] = preg_split("/\r\n\r\n|\n\n/", $raw, 2) + ['', ''];
        $headers = $this->headers($rawHeaders);
        $contentType = $headers['content-type'] ?? '';
        $text = $this->decodeBody($body, $headers['content-transfer-encoding'] ?? null);
        $html = null;

        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $matches)) {
            [$text, $html] = $this->multipartBodies($body, $matches[1]);
        } elseif (str_contains(strtolower($contentType), 'text/html')) {
            $html = $text;
            $text = trim(strip_tags($text));
        }

        return [
            'from_email' => $this->emailAddress($headers['from'] ?? null) ?? 'unknown@example.invalid',
            'to_email' => $this->emailAddress($headers['to'] ?? null),
            'subject' => isset($headers['subject']) ? iconv_mime_decode($headers['subject'], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : null,
            'body_text' => trim($text),
            'body_html' => $html,
            'received_at' => isset($headers['date']) ? date('c', strtotime($headers['date'])) : now()->toIso8601String(),
            'message_id' => $headers['message-id'] ?? null,
        ];
    }

    /** @return resource */
    private function connect(EmailSource $source)
    {
        if (! $source->imap_host || ! $source->imap_port || ! $source->username || ! $source->password) {
            throw new RuntimeException('POP host, port, username and password are required.');
        }

        $transport = $source->encryption === 'ssl' ? 'ssl' : 'tcp';
        $connection = @stream_socket_client(
            "{$transport}://{$source->imap_host}:{$source->imap_port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT
        );

        if (! $connection) {
            throw new RuntimeException($errstr ?: "Could not connect to POP server ({$errno}).");
        }

        stream_set_timeout($connection, 30);
        $this->response($connection);

        if ($source->encryption === 'tls') {
            $this->command($connection, 'STLS');

            if (! stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not start TLS encryption for POP connection.');
            }
        }

        return $connection;
    }

    /** @param resource $connection */
    private function command($connection, string $command, bool $allowClosed = false): string
    {
        fwrite($connection, $command."\r\n");

        return $this->response($connection, $allowClosed);
    }

    /** @param resource $connection */
    private function response($connection, bool $allowClosed = false): string
    {
        $line = fgets($connection);

        if ($line === false) {
            if ($allowClosed) {
                return '';
            }

            throw new RuntimeException('POP server closed the connection unexpectedly.');
        }

        if (! str_starts_with($line, '+OK')) {
            throw new RuntimeException(trim(preg_replace('/^-ERR\s*/', '', $line)));
        }

        return $line;
    }

    /** @param resource $connection @return array<int, string> */
    private function uidList($connection): array
    {
        $this->command($connection, 'UIDL');
        $uids = [];

        foreach ($this->multiLine($connection) as $line) {
            [$messageNumber, $uid] = explode(' ', $line, 2) + [null, null];

            if ($messageNumber && $uid) {
                $uids[(int) $messageNumber] = trim($uid);
            }
        }

        return $uids;
    }

    /** @param resource $connection */
    private function retrieve($connection, int $messageNumber): string
    {
        $this->command($connection, 'RETR '.$messageNumber);

        return implode("\r\n", $this->multiLine($connection));
    }

    /** @param resource $connection @return list<string> */
    private function multiLine($connection): array
    {
        $lines = [];

        while (($line = fgets($connection)) !== false) {
            $line = rtrim($line, "\r\n");

            if ($line === '.') {
                break;
            }

            $lines[] = str_starts_with($line, '..') ? substr($line, 1) : $line;
        }

        return $lines;
    }

    /** @return array<string, string> */
    private function headers(string $rawHeaders): array
    {
        $headers = [];
        $current = null;

        foreach (preg_split('/\r\n|\n/', $rawHeaders) as $line) {
            if (preg_match('/^\s+/', $line) && $current) {
                $headers[$current] .= ' '.trim($line);
                continue;
            }

            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $current = strtolower(trim($name));
            $headers[$current] = trim($value);
        }

        return $headers;
    }

    private function decodeBody(string $body, ?string $encoding): string
    {
        return match (strtolower((string) $encoding)) {
            'base64' => base64_decode($body, true) ?: '',
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    /** @return array{0: string, 1: ?string} */
    private function multipartBodies(string $body, string $boundary): array
    {
        $text = '';
        $html = null;

        foreach (explode('--'.$boundary, $body) as $part) {
            if (! str_contains($part, "\n\n") && ! str_contains($part, "\r\n\r\n")) {
                continue;
            }

            [$rawHeaders, $partBody] = preg_split("/\r\n\r\n|\n\n/", $part, 2) + ['', ''];
            $headers = $this->headers($rawHeaders);
            $decoded = $this->decodeBody($partBody, $headers['content-transfer-encoding'] ?? null);
            $contentType = strtolower($headers['content-type'] ?? '');

            if (str_contains($contentType, 'text/plain') && blank($text)) {
                $text = trim($decoded);
            }

            if (str_contains($contentType, 'text/html') && blank($html)) {
                $html = trim($decoded);
            }
        }

        return [$text ?: trim(strip_tags((string) $html)), $html];
    }

    private function emailAddress(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value, $matches)
            ? strtolower($matches[0])
            : null;
    }
}
