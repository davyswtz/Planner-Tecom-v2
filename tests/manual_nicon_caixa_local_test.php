<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;

$service = app(NiconWebService::class);
$response = $service->renderizarCaixasProximas('Caixa-P1209_P12', 146119);
$caixa = $response['caixas']['Caixa-P1209_P12'] ?? reset($response['caixas']);

echo "=== chaves caixa ===\n";
echo implode(', ', array_keys($caixa ?: [])) . "\n\n";

echo "=== caixa (sem clientes) ===\n";
$resumo = $caixa;
unset($resumo['clientes']);
echo json_encode($resumo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$optica = $caixa['clientes'][array_key_first($caixa['clientes'] ?? [])]['servicos'][0]['cliente_porta_atendimento']['mapeamento_porta_atendimento']['caixa_optica'] ?? null;
echo "=== caixa_optica (aninhado) ===\n";
echo json_encode($optica, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
