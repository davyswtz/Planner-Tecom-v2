<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;
use Illuminate\Support\Facades\Cache;

$service = app(NiconWebService::class);
$ref = new ReflectionClass($service);

$getInfra = $ref->getMethod('getInfra');
$getInfra->setAccessible(true);
$getCliente = $ref->getMethod('getCliente');
$getCliente->setAccessible(true);

$candidatos = [
    ['infra', '/infra/buscar-ponto-juncao', ['id_ponto_juncao' => 102562]],
    ['infra', '/infra/ponto-juncao/102562', []],
    ['infra', '/infra/buscar-caixa-optica', ['id_caixa_optica' => 146119]],
    ['infra', '/infra/caixa-optica/146119', []],
    ['infra', '/infra/buscar-dados-caixa-optica', ['id_caixa_optica' => 146119]],
    ['cliente', '/cliente/conexao/buscar-dados-caixa-optica', ['id_caixa_optica' => 146119]],
];

foreach ($candidatos as [$tipo, $path, $query]) {
    try {
        $resp = $tipo === 'infra'
            ? $getInfra->invoke($service, $path, $query)
            : $getCliente->invoke($service, $path, $query);
        $preview = json_encode($resp, JSON_UNESCAPED_UNICODE);
        echo "OK {$path} => " . substr($preview, 0, 400) . "\n\n";
    } catch (Throwable $e) {
        echo "ERR {$path} => " . substr($e->getMessage(), 0, 120) . "\n\n";
    }
}
