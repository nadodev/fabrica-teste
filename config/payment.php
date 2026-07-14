<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'fake'),
    'fake_outcome' => env('FAKE_PAYMENT_OUTCOME', 'approved'),
    'asaas' => [
        'base_url' => env('ASAAS_BASE_URL', 'https://api.asaas.com/v3'),
        'api_key' => env('ASAAS_API_KEY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
        'live_enabled' => env('ASAAS_LIVE_ENABLED', false),
        'due_days' => env('ASAAS_DUE_DAYS', 3),
        'webhook_max_attempts' => env('ASAAS_WEBHOOK_MAX_ATTEMPTS', 10),
        'webhook_stale_minutes' => env('ASAAS_WEBHOOK_STALE_MINUTES', 15),
    ],
];
