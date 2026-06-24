<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconApiService;
use App\Services\Nicon\NiconWebService;

$web = app(NiconWebService::class);
$api = app(NiconApiService::class);
$ids = array_column($web->listarClientesPorCaixa(1659, 'p1209'), 'id_cliente_servico');

$t = microtime(true);
try {
    $api->buscarSinalOnu((int) $ids[0]);
    echo 'API app-tecnico 1 sinal: ' . round((microtime(true) - $t) * 1000) . "ms\n";
} catch (Throwable $e) {
    echo 'API erro: ' . $e->getMessage() . "\n";
}

$t = microtime(true);
$web->buscarSinalAtualCliente((int) $ids[0]);
echo 'Web 1 sinal: ' . round((microtime(true) - $t) * 1000) . "ms\n";
