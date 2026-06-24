<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;

$service = app(NiconWebService::class);

try {
    echo "=== buscar caixas id_cidade=1659 ===\n";
    $caixas = $service->buscarCaixasComCliente(1659);
    $itens = $caixas['itens'] ?? [];
    echo 'total caixas: ' . count($itens) . "\n";

    echo "\n=== renderizar caixa P1209 ===\n";
    $clientes = $service->listarClientesPorCaixa(1659, 'Caixa-P1209_P12');
    echo 'total clientes: ' . count($clientes) . "\n";
    echo json_encode(array_slice($clientes, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    echo "\n=== sinais completos ===\n";
    $comSinais = $service->buscarSinaisPorCidadeECaixa(1659, 'P1209_P12');
    echo 'total: ' . count($comSinais) . "\n";
    echo json_encode(array_slice($comSinais, 0, 1), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
}
