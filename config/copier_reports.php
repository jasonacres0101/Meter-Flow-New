<?php

return [
    'imap' => [
        'host' => env('COPIER_REPORTS_IMAP_HOST'),
        'port' => env('COPIER_REPORTS_IMAP_PORT', 993),
        'encryption' => env('COPIER_REPORTS_IMAP_ENCRYPTION', 'ssl'),
        'folder' => env('COPIER_REPORTS_IMAP_FOLDER', 'INBOX'),
        'username' => env('COPIER_REPORTS_IMAP_USERNAME'),
        'password' => env('COPIER_REPORTS_IMAP_PASSWORD'),
        'delete_after_ingest' => env('COPIER_REPORTS_IMAP_DELETE_AFTER_INGEST', false),
    ],
    'microsoft_graph' => [
        'base_url' => env('MICROSOFT_GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'),
        'token_url' => env('MICROSOFT_GRAPH_TOKEN_URL', 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token'),
    ],
];
