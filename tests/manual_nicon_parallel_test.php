<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;

$service = app(NiconWebService::class);
$clientes = $service->listarClientesPorCaixa(1659, 'p1209');
$ids = array_column($clientes, 'id_cliente_servico');

$t = microtime(true);
$service->buscarSinalAtualCliente((int) $ids[0]);
echo '1 sinal sequencial: ' . round((microtime(true) - $t) * 1000) . "ms\n";

$ref = new ReflectionClass($service);
$m = $ref->getMethod('buscarSinaisAtuaisParalelo');
$m->setAccessible(true);

$t = microtime(true);
$m->invoke($service, array_slice($ids, 0, 3));
echo '3 sinais paralelo: ' . round((microtime(true) - $t) * 1000) . "ms\n";

$t = microtime(true);
$m->invoke($service, $ids);
echo '6 sinais paralelo: ' . round((microtime(true) - $t) * 1000) . "ms\n";
