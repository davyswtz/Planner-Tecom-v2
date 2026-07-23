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
         | Goval → conversa 4143 (subtópicos por tarefa).
         | Override via NICON_CHAT_CONVERSAS={"Vale do Aço":1234}
         */
        'chat_enabled' => filter_var(env('NICON_CHAT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'chat_conversa_id' => (int) env('NICON_CHAT_CONVERSA_ID', 0),
        'chat_conversas' => array_replace(
            [
                'Goval' => 4143,
                'GOVAL' => 4143,
                'Governador Valadares' => 4143,
                'goval' => 4143,
                'governador valadares' => 4143,
                'Vale do Aço' => 4140,
                'Vale do Aco' => 4140,
                'VALE_DO_ACO' => 4140,
                'vale do aço' => 4140,
                'vale do aco' => 4140,
                'Caratinga' => 4140,
                'caratinga' => 4140,
                'Teste' => 4180,
                'TESTE' => 4180,
                'teste' => 4180,
                'Backup' => 4180,
                'backup' => 4180,
            ],
            array_map('intval', json_decode(env('NICON_CHAT_CONVERSAS', '{}'), true) ?: [])
        ),
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
    | Telegram Bot (paralelo ao Google Chat / Nicon).
    | Post no CANAL + comentários no grupo de discussão vinculado.
    | - Teste       → Central de Projetos Dev (-1003553532320) / BACKUP (-1004478110795)
    | - Goval       → MULTISKILL GV (-1004326360122) / Chat MULTISKILL GV backup (-1003742115644)
    | - Vale do Aço → MULTISKILL VALE DO AÇO (-1004373600093) / BACKUP VA (-1004461293839)
    | Override:
    |   TELEGRAM_CHAT_IDS={"Goval":-1004326360122,"Vale do Aço":-1004373600093,"Teste":-1003553532320}
    |   TELEGRAM_DISCUSSION_CHAT_IDS={"Goval":-1003742115644,"Vale do Aço":-1004461293839,"Teste":-1004478110795}
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'api_base' => rtrim(env('TELEGRAM_API_BASE', 'https://api.telegram.org'), '/'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 30),
        'chat_enabled' => filter_var(env('TELEGRAM_CHAT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'discussion_chat_id' => env('TELEGRAM_DISCUSSION_CHAT_ID'),
        'chat_ids' => array_replace(
            [
                'Teste' => -1003553532320,
                'TESTE' => -1003553532320,
                'teste' => -1003553532320,
                'Backup' => -1003553532320,
                'backup' => -1003553532320,
                'Goval' => -1004326360122,
                'GOVAL' => -1004326360122,
                'goval' => -1004326360122,
                'Governador Valadares' => -1004326360122,
                'Vale do Aço' => -1004373600093,
                'Vale do Aco' => -1004373600093,
                'VALE_DO_ACO' => -1004373600093,
                'vale do aço' => -1004373600093,
                'vale do aco' => -1004373600093,
            ],
            (static function (): array {
                $decoded = json_decode(env('TELEGRAM_CHAT_IDS', '{}'), true) ?: [];
                $out = [];
                foreach ($decoded as $k => $v) {
                    $out[$k] = is_numeric($v) ? (int) $v : $v;
                }

                return $out;
            })()
        ),
        'discussion_chat_ids' => array_replace(
            [
                'Teste' => -1004478110795,
                'TESTE' => -1004478110795,
                'teste' => -1004478110795,
                'Backup' => -1004478110795,
                'backup' => -1004478110795,
                'Goval' => -1003742115644,
                'GOVAL' => -1003742115644,
                'goval' => -1003742115644,
                'Governador Valadares' => -1003742115644,
                'Vale do Aço' => -1004461293839,
                'Vale do Aco' => -1004461293839,
                'VALE_DO_ACO' => -1004461293839,
                'vale do aço' => -1004461293839,
                'vale do aco' => -1004461293839,
            ],
            (static function (): array {
                $decoded = json_decode(env('TELEGRAM_DISCUSSION_CHAT_IDS', '{}'), true) ?: [];
                $out = [];
                foreach ($decoded as $k => $v) {
                    $out[$k] = is_numeric($v) ? (int) $v : $v;
                }

                return $out;
            })()
        ),
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
