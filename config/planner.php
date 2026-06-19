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

];
