<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Nicon\NiconWebService;
use Illuminate\Support\Facades\Cache;

$service = app(NiconWebService::class);

function bench(string $label, callable $fn): void
{
    $inicio = microtime(true);
    $resultado = $fn();
    $segundos = round(microtime(true) - $inicio, 2);
    $qtd = is_array($resultado) ? count($resultado) : 0;
    echo "{$label}: {$segundos}s ({$qtd} clientes)\n";
}

echo "=== 1ª busca (cache frio) ===\n";
Cache::forget('nicon_caixas_lista_1659');
Cache::forget('nicon_caixa_resolve_1659_' . md5('P1209_P12'));
bench('completa', fn () => $service->buscarSinaisPorCidadeECaixa(1659, 'p1209'));

echo "\n=== 2ª busca (cache quente) ===\n";
bench('completa', fn () => $service->buscarSinaisPorCidadeECaixa(1659, 'p1209'));

echo "\n=== outra caixa (lista em cache) ===\n";
bench('b101', fn () => $service->buscarSinaisPorCidadeECaixa(1659, 'b101'));
