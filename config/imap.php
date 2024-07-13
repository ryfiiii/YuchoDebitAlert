<?php

return [
    'accounts' => [
        'default' => [
            'host'          => env('IMAP_HOST', 'imap.mail.me.com'),
            'port'          => env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username'      => env('IMAP_USERNAME'),
            'password'      => env('IMAP_PASSWORD'),
        ],
    ],
];
