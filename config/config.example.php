<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Vernocchi Photography',
        'url' => 'https://vernocchi.es',
        'debug' => false,
        'default_language' => 'es',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'user' => 'your_database_user',
        'pass' => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'vernocchi_session',
        'lifetime' => 1800,
        'remember_days' => 30,
    ],
    'totp' => [
        'issuer' => 'Vernocchi Photography',
        'digits' => 6,
        'period' => 30,
        'algorithm' => 'sha1',
    ],
    'turnstile' => [
        'site_key' => '',
        'secret_key' => '',
    ],
];
