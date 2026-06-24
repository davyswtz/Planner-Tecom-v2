<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'nicon' => [
        'base_url' => rtrim(env('NICON_BASE_URL', ''), '/'),
        'email' => env('NICON_EMAIL'),
        'password' => env('NICON_PASSWORD'),
        'two_factor' => env('NICON_2FA'),
        'timeout' => (int) env('NICON_TIMEOUT', 120),
        'status_servico' => array_map('intval', array_filter(explode(',', env('NICON_STATUS_SERVICO', '10,12,11,13')))),
        'caixas_cache_minutes' => (int) env('NICON_CAIXAS_CACHE_MINUTES', 360),
        'caixa_resolve_cache_minutes' => (int) env('NICON_CAIXA_RESOLVE_CACHE_MINUTES', 1440),
        'sinal_concorrencia' => max(1, (int) env('NICON_SINAL_CONCORRENCIA', 4)),
        'sinal_tentativas' => max(1, (int) env('NICON_SINAL_TENTATIVAS', 3)),
        'cidades' => [
            1659 => 'Governador Valadares',
        ],
    ],

    /*
    | Verificação SSL do cliente HTTP (Google Chat, Nicon, etc.).
    | Em local no Windows, defina HTTP_VERIFY_SSL=false se aparecer cURL error 60.
    */
    'http_verify_ssl' => filter_var(
        env('HTTP_VERIFY_SSL', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOL
    ),

];
