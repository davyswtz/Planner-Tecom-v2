<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;

$service = app(NiconWebService::class);
$buscas = ['p1209', 'P1209', 'p1209_p12', 'Caixa-P1209_P12', 'caixa p1209 p12'];

foreach ($buscas as $busca) {
    try {
        $clientes = $service->listarClientesPorCaixa(1659, $busca);
        $caixa = $clientes[0]['caixa'] ?? '?';
        echo "\"{$busca}\" => {$caixa} (" . count($clientes) . " clientes)\n";
    } catch (Throwable $e) {
        echo "\"{$busca}\" => ERRO: {$e->getMessage()}\n";
    }
}
