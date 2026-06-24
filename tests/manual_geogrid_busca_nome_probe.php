<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeoGrid\GeoGridService;

$termo = $argv[1] ?? '1000010228';
$service = app(GeoGridService::class);

echo "=== Probe busca por nome: {$termo} ===\n\n";

$rotas = [
    'menu/itens + pesquisa' => fn () => $service->buscarItensMenu([
        'pesquisa' => '%' . $termo . '%',
        'itens' => ['caixa'],
    ]),
    'buscarCaixaPorTermo' => fn () => $service->buscarCaixaPorTermo($termo),
];

foreach ($rotas as $nome => $fn) {
    echo "--- {$nome} ---\n";
    try {
        $resultado = $fn();
        if (is_array($resultado) && isset($resultado['sigla'])) {
            echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo 'registros: ' . count($resultado) . "\n";
            if (! empty($resultado[0])) {
                $item = $resultado[0];
                echo 'primeiro: ' . json_encode([
                    'id' => $item['id'] ?? $item['dados']['id'] ?? null,
                    'sigla' => $item['sigla'] ?? $item['dados']['sigla'] ?? null,
                    'item' => $item['item'] ?? $item['dados']['item'] ?? null,
                ], JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    } catch (Throwable $e) {
        echo 'ERRO: ' . $e->getMessage() . "\n";
    }
    echo "\n";
}
