<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconApiService;

$api = app(NiconApiService::class);
$sinal = $api->buscarSinalOnu(314661);
echo json_encode($sinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
