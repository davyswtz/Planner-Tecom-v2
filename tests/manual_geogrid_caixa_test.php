<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeoGrid\GeoGridService;

$service = app(GeoGridService::class);
$termo = $argv[1] ?? 'p1209';

try {
    echo "=== GeoGrid buscar caixa: {$termo} ===\n";
    $caixa = $service->buscarCaixaPorTermo($termo);

    if ($caixa === null) {
        echo "Nenhuma caixa encontrada.\n";
        exit(1);
    }

    echo json_encode($caixa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    if (! empty($caixa['id'])) {
        echo "\n=== mapa em lote (ids[]) ===\n";
        $mapa = $service->obterMapaItens([(int) $caixa['id']]);
        echo json_encode($mapa[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . "\n";
    exit(1);
}
