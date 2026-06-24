<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;

$service = app(NiconWebService::class);

function ms(string $label, callable $fn) {
    $t = microtime(true);
    $r = $fn();
    echo $label . ': ' . round((microtime(true) - $t) * 1000) . "ms\n";
    return $r;
}

$clientes = ms('listar clientes', fn () => $service->listarClientesPorCaixa(1659, 'p1209'));
$ids = array_column($clientes, 'id_cliente_servico');
ms('sinal lote', fn () => $service->buscarSinalClientes($ids));
ms('sinais completos', fn () => $service->buscarSinaisCompletos($ids));
