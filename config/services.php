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

    'geogrid' => [
        'base_url' => rtrim(env('GEOGRID_BASE_URL', ''), '/'),
        'auth_url' => env('GEOGRID_AUTH_URL'),
        'user' => env('GEOGRID_USER'),
        'password' => env('GEOGRID_PASSWORD'),
        'version' => env('GEOGRID_VERSION', '199.5'),
        'timeout' => (int) env('GEOGRID_TIMEOUT', 120),
        'pasta_caixas' => (int) env('GEOGRID_PASTA_CAIXAS', 1713),
        'caixas_cache_minutes' => (int) env('GEOGRID_CAIXAS_CACHE_MINUTES', 360),
        'caixas_mapa_lote' => (int) env('GEOGRID_CAIXAS_MAPA_LOTE', 60),
    ],

    'nicon' => [
        'base_url' => rtrim(env('NICON_BASE_URL', ''), '/'),
        'email' => env('NICON_EMAIL'),
        'password' => env('NICON_PASSWORD'),
        'timeout' => (int) env('NICON_TIMEOUT', 120),
        'status_servico' => array_map('intval', array_filter(explode(',', env('NICON_STATUS_SERVICO', '10,12,11,13')))),
        'caixas_cache_minutes' => (int) env('NICON_CAIXAS_CACHE_MINUTES', 360),
        'caixa_resolve_cache_minutes' => (int) env('NICON_CAIXA_RESOLVE_CACHE_MINUTES', 1440),
        'sinal_concorrencia' => max(1, (int) env('NICON_SINAL_CONCORRENCIA', 4)),
        'sinal_tentativas' => max(1, (int) env('NICON_SINAL_TENTATIVAS', 3)),
        /*
         | Chat Nicon (paralelo ao webhook Google Chat).
         | NICON_CHAT_ENABLED=true + NICON_CHAT_CONVERSA_ID=4180 para testar.
         | Opcional: NICON_CHAT_CONVERSAS={"Goval":4180,"Vale do Aço":4180}
         */
        'chat_enabled' => filter_var(env('NICON_CHAT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'chat_conversa_id' => (int) env('NICON_CHAT_CONVERSA_ID', 0),
        'chat_conversas' => json_decode(env('NICON_CHAT_CONVERSAS', '{}'), true) ?: [],
        'cidades' => [
            1659 => 'Governador Valadares',
            1701 => 'Ipatinga',
        ],
        'regiao_cidade' => [
            'Goval' => 1659,
            'Vale do Aço' => 1701,
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
