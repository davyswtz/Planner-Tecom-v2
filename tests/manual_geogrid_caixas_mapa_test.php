<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeoGrid\GeoGridService;
use Illuminate\Support\Facades\Cache;

Cache::forget('geogrid_caixas_gv_mapa_1713');
Cache::forget('geogrid_caixas_grupo_1713');

$service = app(GeoGridService::class);

echo "=== GeoGrid — caixas GV para mapa ===\n";

$inicio = microtime(true);
$resultado = $service->listarCaixasGovernadorValadares();
$duracao = round(microtime(true) - $inicio, 2);

echo "Total IDs: {$resultado['total_ids']}\n";
echo "Com coordenadas: {$resultado['total_com_coordenadas']}\n";
echo "Tempo: {$duracao}s\n";

if (! empty($resultado['caixas'])) {
    $amostra = $resultado['caixas'][0];
    echo "Amostra: " . json_encode($amostra, JSON_UNESCAPED_UNICODE) . "\n";
}
