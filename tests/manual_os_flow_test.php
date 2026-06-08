<?php

/**
 * Teste manual: OS vinculada à tarefa pai + exclusão real no banco.
 * Uso: php tests/manual_os_flow_test.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OpTask;
use App\Models\User;
use App\Services\OpTaskService;
use App\Services\RompimentoService;

$results = [];
$pass = fn (string $name, bool $ok, string $detail = '') => $results[] = compact('name', 'ok', 'detail');

echo "=== Teste OS vinculada + exclusão no banco ===\n\n";

// ── 1. Serviço: criar rompimento pai ──────────────────────────────────────
$rompimentoService = app(RompimentoService::class);
$opTaskService = app(OpTaskService::class);

$parent = $rompimentoService->createRompimento([
    'titulo' => 'Rompimento — TESTE-AUTO-' . time(),
    'cto' => 'TESTE-AUTO',
    'descricao' => 'Fibra cortada',
    'regiao' => 'Goval',
    'responsavel' => 'Teste Automatizado',
    'clientesAfetados' => 1,
    'prioridade' => 'Média',
    'coordenadas' => '-18.8517, -41.9494',
    'localizacao_texto' => 'Endereço teste',
    'status' => 'Criada',
    'categoria' => 'rompimentos',
]);

$parentId = $parent->id;
$pass('Criar tarefa pai (rompimento)', OpTask::find($parentId) !== null, "id={$parentId}");

// ── 2. Serviço: criar OS filha vinculada ──────────────────────────────────
$os = $opTaskService->createOpTask([
    'titulo' => 'OS — TESTE AUTO EXCLUSAO',
    'responsavel' => 'Tecnico Teste',
    'status' => 'Aberta',
    'categoria' => 'ordem-servico',
    'parent_task_id' => $parentId,
    'regiao' => 'Goval',
]);

$osId = $os->id;
$osNoBanco = OpTask::find($osId);
$pass(
    'Criar OS vinculada à tarefa pai',
    $osNoBanco && (int) $osNoBanco->parent_task_id === (int) $parentId && $osNoBanco->categoria === 'ordem-servico',
    "os_id={$osId}, parent_task_id={$osNoBanco?->parent_task_id}"
);

// ── 3. Listagem (mesma query do RompimentoController::listarOS) ───────────
$listadas = OpTask::where('parent_task_id', $parentId)
    ->where('categoria', 'ordem-servico')
    ->pluck('id')
    ->all();

$pass(
    'Listar OS da tarefa pai',
    in_array($osId, $listadas, true),
    'ids=' . implode(',', $listadas)
);

// ── 4. Exclusão via OpTaskService (mesma lógica do DELETE /api/op-tasks) ──
try {
    $opTaskService->deleteOpTask($osNoBanco);
    $pass('Excluir OS via deleteOpTask', true, "os_id={$osId}");
} catch (Throwable $e) {
    $pass('Excluir OS via deleteOpTask', false, $e->getMessage());
}

$aindaExiste = OpTask::find($osId);
$pass('OS removida do banco após exclusão', $aindaExiste === null, $aindaExiste ? "ainda existe id={$osId}" : 'registro não encontrado (ok)');

// ── 5. Tarefa pai permanece após excluir OS ───────────────────────────────
$paiAposExclusao = OpTask::find($parentId);
$pass(
    'Tarefa pai permanece após excluir OS',
    $paiAposExclusao !== null && $paiAposExclusao->categoria === 'rompimentos',
    "parent_id={$parentId}"
);

// ── 6. Proteção: não excluir rompimento pelo endpoint de OS ───────────────
try {
    $opTaskService->deleteOpTask($paiAposExclusao);
    $pass('Bloquear exclusão de tarefa pai via deleteOpTask', false, 'deveria lançar exceção');
} catch (InvalidArgumentException $e) {
    $pass('Bloquear exclusão de tarefa pai via deleteOpTask', true, $e->getMessage());
}

// ── Limpeza: remove rompimento de teste ─────────────────────────────────
OpTask::where('id', $parentId)->delete();
$pass('Limpeza do rompimento de teste', OpTask::find($parentId) === null, "parent_id={$parentId}");

// ── 7. Teste HTTP (se servidor local estiver rodando) ─────────────────────
$user = User::first();
if ($user) {
    $token = $user->createToken('test_os_flow')->plainTextToken;
    $baseUrl = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');

    $httpParent = $rompimentoService->createRompimento([
        'titulo' => 'Rompimento — HTTP-TEST-' . time(),
        'cto' => 'HTTP-TEST',
        'descricao' => 'Fibra cortada',
        'regiao' => 'Goval',
        'responsavel' => 'Teste HTTP',
        'clientesAfetados' => 1,
        'prioridade' => 'Média',
        'status' => 'Criada',
        'categoria' => 'rompimentos',
    ]);

    $httpOs = $opTaskService->createOpTask([
        'titulo' => 'OS — HTTP TEST',
        'responsavel' => 'Tecnico HTTP',
        'status' => 'Aberta',
        'categoria' => 'ordem-servico',
        'parent_task_id' => $httpParent->id,
        'regiao' => 'Goval',
    ]);

    $headers = "Authorization: Bearer {$token}\r\nAccept: application/json\r\nContent-Type: application/json\r\n";

    $ctxList = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => $headers,
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $listResp = @file_get_contents("{$baseUrl}/api/rompimentos/{$httpParent->id}/os", false, $ctxList);
    $listJson = json_decode($listResp ?: '{}', true);
    $listIds = array_column($listJson['os'] ?? [], 'id');
    $pass(
        'HTTP GET /api/rompimentos/{id}/os',
        in_array($httpOs->id, $listIds, true),
        'status=' . ($http_response_header[0] ?? 'sem resposta') . ' ids=' . implode(',', $listIds)
    );

    $ctxDel = stream_context_create(['http' => [
        'method' => 'DELETE',
        'header' => $headers,
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $delResp = @file_get_contents("{$baseUrl}/api/op-tasks/{$httpOs->id}", false, $ctxDel);
    $delJson = json_decode($delResp ?: '{}', true);
    $pass(
        'HTTP DELETE /api/op-tasks/{id}',
        str_contains($http_response_header[0] ?? '', '200'),
        ($http_response_header[0] ?? 'sem resposta') . ' msg=' . ($delJson['message'] ?? '')
    );

    $pass(
        'HTTP: OS ausente no banco após DELETE',
        OpTask::find($httpOs->id) === null,
        'os_id=' . $httpOs->id
    );

    $user->tokens()->where('name', 'test_os_flow')->delete();
    OpTask::where('id', $httpParent->id)->delete();
} else {
    $pass('HTTP tests (usuário disponível)', false, 'Nenhum usuário no banco');
}

// ── Relatório ─────────────────────────────────────────────────────────────
echo str_pad('TESTE', 45) . str_pad('RESULTADO', 12) . "DETALHE\n";
echo str_repeat('-', 90) . "\n";

$total = count($results);
$ok = count(array_filter($results, fn ($r) => $r['ok']));

foreach ($results as $r) {
    echo str_pad($r['name'], 45)
        . str_pad($r['ok'] ? 'PASS' : 'FAIL', 12)
        . $r['detail'] . "\n";
}

echo str_repeat('-', 90) . "\n";
echo "Total: {$ok}/{$total} passaram\n";

exit($ok === $total ? 0 : 1);
