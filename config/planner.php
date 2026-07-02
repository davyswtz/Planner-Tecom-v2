<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tempo real (WebSocket)
    |--------------------------------------------------------------------------
    |
    | Local: BROADCAST_CONNECTION=reverb (+ php artisan reverb:start).
    | Hostinger (domínio + MySQL, sem VPS): BROADCAST_CONNECTION=pusher.
    |
    */

    'realtime' => [
        'driver' => env('BROADCAST_CONNECTION', 'null'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anexos no Google Chat
    |--------------------------------------------------------------------------
    |
    | URLs públicas HTTPS para o Google Chat buscar imagens da OS (sem login).
    | Defina PLANNER_PUBLIC_URL com o domínio real (ex.: https://planner.tecom.com.br).
    |
    */

    'anexos_chat' => [
        // Não use env() aninhado — quebra com config:cache no deploy.
        'public_url' => env('PLANNER_PUBLIC_URL') ?: env('APP_URL', 'http://localhost'),
        'ttl_horas' => (int) env('PLANNER_ANEXOS_CHAT_TTL_HORAS', 72),
        'max_imagens_por_mensagem' => 10,
    ],

];
