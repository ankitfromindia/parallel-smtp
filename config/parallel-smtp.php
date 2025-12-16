<?php

return [
    'max_connections' => env('PARALLEL_SMTP_MAX_CONNECTIONS', 10),
    'messages_per_connection' => env('PARALLEL_SMTP_MESSAGES_PER_CONNECTION', 100),
    
    'smtp' => [
        'host' => env('PARALLEL_SMTP_HOST'),
        'port' => env('PARALLEL_SMTP_PORT', 587),
        'username' => env('PARALLEL_SMTP_USERNAME'),
        'password' => env('PARALLEL_SMTP_PASSWORD'),
        'encryption' => env('PARALLEL_SMTP_ENCRYPTION', 'tls'),
    ],
];
