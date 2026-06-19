<?php

/**
 * Teste manual: criar/editar técnico e verificar região em tecnicos.
 * Uso: php tests/manual_tecnico_regiao_test.php
 */

use App\Http\Controllers\Api\UsuarioController;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$username = 'teste.tecnico.' . bin2hex(random_bytes(3));
$controller = app(UsuarioController::class);
$ok = 0;
$fail = 0;

function pass(string $label, bool $cond, string $detail = ''): void
{
    global $ok, $fail;
    if ($cond) {
        $ok++;
        echo "[OK] {$label}" . ($detail ? " — {$detail}" : '') . PHP_EOL;
    } else {
        $fail++;
        echo "[FALHA] {$label}" . ($detail ? " — {$detail}" : '') . PHP_EOL;
    }
}

echo "=== Teste região técnico no banco ===\n\n";

try {
    Schema::hasTable('tecnicos') || DB::statement('SELECT 1');

    $storeRequest = Request::create('/api/usuarios', 'POST', [
        'username' => $username,
        'funcao' => 'tecnico',
        'regiao' => 'Governador Valadares',
    ]);
    $storeRequest->setUserResolver(fn () => User::first());
    $storeResponse = $controller->store($storeRequest);
    pass('Criar técnico via API', $storeResponse->getStatusCode() === 201, "username={$username}");

    $tecnico = Tecnico::where('username', $username)->first();
    pass('Registro em tecnicos existe', $tecnico !== null);
    pass('Região salva na criação', ($tecnico?->regiao ?? '') === 'Governador Valadares', "regiao={$tecnico?->regiao}");

    $updateRequest = Request::create("/api/usuarios/{$username}", 'PUT', [
        'username' => $username,
        'funcao' => 'tecnico',
        'regiao' => 'Vale do Aço',
    ]);
    $updateRequest->setUserResolver(fn () => User::first());
    $updateResponse = $controller->update($updateRequest, $username);
    pass('Editar técnico via API', $updateResponse->getStatusCode() === 200);

    $tecnicoAtualizado = Tecnico::where('username', $username)->first();
    pass('Região atualizada na edição', ($tecnicoAtualizado?->regiao ?? '') === 'Vale do Aço', "regiao={$tecnicoAtualizado?->regiao}");

    $json = json_decode($updateResponse->getContent(), true);
    pass('Resposta API traz região', ($json['usuario']['regiao'] ?? null) === 'Vale do Aço');
} catch (Throwable $e) {
    pass('Execução sem exceção', false, $e->getMessage());
} finally {
    if (Schema::hasTable('tecnicos')) {
        Tecnico::where('username', $username)->delete();
    }
    User::where('username', $username)->delete();
}

echo "\nResultado: {$ok} ok, {$fail} falha(s)\n";
exit($fail > 0 ? 1 : 0);
