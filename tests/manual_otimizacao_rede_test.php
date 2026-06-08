<?php

/**
 * Teste manual: criar, editar, OS vinculada e exclusão de otimização de rede no banco.
 * Uso: php tests/manual_otimizacao_rede_test.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OpTask;
use App\Models\User;
use App\Services\OpTaskService;
use App\Services\OtimizaçãoDeRedeService;

$results = [];
$pass = function (string $name, bool $ok, string $detail = '') use (&$results) {
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
};

echo "=== Teste Otimização de Rede — persistência no banco ===\n\n";

$otimizacaoService = app(OtimizaçãoDeRedeService::class);
$opTaskService = app(OpTaskService::class);
$suffix = (string) time();

// ── 1. Criar tarefa ───────────────────────────────────────────────────────
$criada = $otimizacaoService->createOtimizaçãoDeRede([
    'titulo' => "Otimização — TESTE-AUTO-{$suffix}",
    'cto' => 'GVA9999',
    'descricao' => 'Fusão',
    'regiao' => 'Goval',
    'responsavel' => 'Tecnico Teste',
    'prioridade' => 'Média',
    'protocolo' => "PROT-{$suffix}",
    'numero_os' => '123456',
    'coordenadas' => '-18.8517, -41.9494',
    'localizacao_texto' => 'Endereço teste automatizado',
    'prazo' => '2026-06-15',
    'status' => 'Criada',
]);

$parentId = $criada->id;
$noBanco = OpTask::find($parentId);

$pass(
    'Criar otimização de rede',
    $noBanco !== null
        && $noBanco->categoria === 'otimizacao-rede'
        && $noBanco->cto === 'GVA9999'
        && $noBanco->descricao === 'Fusão'
        && $noBanco->protocolo === "PROT-{$suffix}"
        && $noBanco->numero_os === '123456'
        && !empty($noBanco->taskCode),
    "id={$parentId}, taskCode={$noBanco?->taskCode}, categoria={$noBanco?->categoria}"
);

// ── 2. Editar tarefa ───────────────────────────────────────────────────────
$atualizada = $otimizacaoService->updateOtimizaçãoDeRede($noBanco, [
    'titulo' => "Otimização — EDITADA-{$suffix}",
    'descricao' => 'Splitter',
    'prioridade' => 'Alta',
    'responsavel' => 'Tecnico Editado',
    'status' => 'Em andamento',
]);

$editadaNoBanco = OpTask::find($parentId);
$pass(
    'Editar otimização de rede',
    $editadaNoBanco->titulo === "Otimização — EDITADA-{$suffix}"
        && $editadaNoBanco->descricao === 'Splitter'
        && $editadaNoBanco->prioridade === 'Alta'
        && $editadaNoBanco->responsavel === 'Tecnico Editado'
        && $editadaNoBanco->status === 'Em andamento',
    "titulo={$editadaNoBanco->titulo}, status={$editadaNoBanco->status}"
);

// ── 3. Criar OS vinculada ───────────────────────────────────────────────────
$os = $opTaskService->createOpTask([
    'titulo' => 'OS — TESTE AUTO OTIMIZACAO',
    'responsavel' => 'Tecnico OS',
    'status' => 'Aberta',
    'categoria' => 'ordem-servico',
    'parent_task_id' => $parentId,
    'regiao' => 'Goval',
]);

$osId = $os->id;
$osNoBanco = OpTask::find($osId);
$pass(
    'Criar OS vinculada à otimização',
    $osNoBanco
        && (int) $osNoBanco->parent_task_id === (int) $parentId
        && $osNoBanco->categoria === 'ordem-servico',
    "os_id={$osId}, parent_task_id={$osNoBanco?->parent_task_id}"
);

// ── 4. Editar OS ────────────────────────────────────────────────────────────
$opTaskService->updateOpTask($osNoBanco, [
    'titulo' => 'OS — EDITADA AUTO',
    'responsavel' => 'Tecnico OS Editado',
    'status' => 'Em andamento',
]);
$osEditada = OpTask::find($osId);
$pass(
    'Editar OS vinculada',
    $osEditada->titulo === 'OS — EDITADA AUTO'
        && $osEditada->responsavel === 'Tecnico OS Editado'
        && $osEditada->status === 'Em andamento',
    "titulo={$osEditada->titulo}, status={$osEditada->status}"
);

// ── 5. Bloquear finalização com OS pendente ─────────────────────────────────
$bloqueou = false;
try {
    $otimizacaoService->updateOtimizaçãoDeRede($editadaNoBanco, ['status' => 'Finalizada']);
} catch (Throwable $e) {
    $bloqueou = str_contains($e->getMessage(), 'Finalize todas as OS');
}
$pass('Bloquear finalização com OS pendente', $bloqueou, $bloqueou ? 'regra aplicada' : 'deveria bloquear');

// ── 6. Excluir OS ───────────────────────────────────────────────────────────
$opTaskService->deleteOpTask($osEditada);
$pass('Excluir OS do banco', OpTask::find($osId) === null, "os_id={$osId}");

// ── 7. Excluir tarefa (com cascade de OS) ───────────────────────────────────
$osExtra = $opTaskService->createOpTask([
    'titulo' => 'OS — CASCADE TEST',
    'responsavel' => 'Tecnico Cascade',
    'status' => 'Aberta',
    'categoria' => 'ordem-servico',
    'parent_task_id' => $parentId,
    'regiao' => 'Goval',
]);
$osExtraId = $osExtra->id;

$otimizacaoService->deleteOtimizaçãoDeRede($editadaNoBanco);
$pass(
    'Excluir otimização e OS vinculadas',
    OpTask::find($parentId) === null && OpTask::find($osExtraId) === null,
    "parent_id={$parentId}, os_extra_id={$osExtraId}"
);

// ── 8. Testes HTTP via kernel Laravel (sem servidor externo) ────────────────
$user = User::first();
if ($user) {
    $token = $user->createToken('test_otimizacao_rede')->plainTextToken;
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $authServer = [
        'HTTP_AUTHORIZATION' => "Bearer {$token}",
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ];

    $postPayload = [
        'titulo' => "Otimização — HTTP-{$suffix}",
        'cto' => 'HTTP999',
        'descricao' => 'Cabo',
        'regiao' => 'Goval',
        'responsavel' => 'Tecnico HTTP',
        'prioridade' => 'Média',
        'protocolo' => "HTTP-{$suffix}",
        'numero_os' => '654321',
        'coordenadas' => '-18.8517, -41.9494',
        'status' => 'Criada',
    ];

    $postRequest = \Illuminate\Http\Request::create(
        '/api/otimizacao-rede',
        'POST',
        [],
        [],
        [],
        $authServer,
        json_encode($postPayload)
    );
    $postResponse = $kernel->handle($postRequest);
    $postJson = json_decode($postResponse->getContent(), true);
    $httpId = $postJson['otimizacaoDeRede']['id'] ?? null;
    $httpTaskCode = $postJson['otimizacaoDeRede']['taskCode'] ?? null;

    $pass(
        'HTTP POST /api/otimizacao-rede',
        $postResponse->getStatusCode() === 201 && $httpId !== null,
        "status={$postResponse->getStatusCode()} id={$httpId}"
    );

    $httpNoBanco = $httpId ? OpTask::find($httpId) : null;
    $pass(
        'HTTP POST persistiu no banco',
        $httpNoBanco
            && $httpNoBanco->categoria === 'otimizacao-rede'
            && $httpNoBanco->cto === 'HTTP999'
            && $httpNoBanco->protocolo === "HTTP-{$suffix}",
        "taskCode={$httpTaskCode}, cto={$httpNoBanco?->cto}"
    );

    if ($httpId) {
        $putRequest = \Illuminate\Http\Request::create(
            "/api/otimizacao-rede/{$httpId}",
            'PUT',
            [],
            [],
            [],
            $authServer,
            json_encode([
                'titulo' => "Otimização — HTTP-EDIT-{$suffix}",
                'descricao' => 'Conector',
                'status' => 'Em andamento',
            ])
        );
        $putResponse = $kernel->handle($putRequest);
        $putJson = json_decode($putResponse->getContent(), true);
        $httpEditada = OpTask::find($httpId);

        $pass(
            'HTTP PUT /api/otimizacao-rede/{id}',
            $putResponse->getStatusCode() === 200
                && $httpEditada?->titulo === "Otimização — HTTP-EDIT-{$suffix}"
                && $httpEditada?->descricao === 'Conector'
                && $httpEditada?->status === 'Em andamento',
            "status={$putResponse->getStatusCode()} msg=" . ($putJson['message'] ?? '')
        );

        $delRequest = \Illuminate\Http\Request::create(
            "/api/otimizacao-rede/{$httpId}",
            'DELETE',
            [],
            [],
            [],
            $authServer
        );
        $delResponse = $kernel->handle($delRequest);
        $pass(
            'HTTP DELETE /api/otimizacao-rede/{id}',
            $delResponse->getStatusCode() === 200 && OpTask::find($httpId) === null,
            "status={$delResponse->getStatusCode()} id={$httpId}"
        );
    }

    $user->tokens()->where('name', 'test_otimizacao_rede')->delete();
} else {
    $pass('HTTP tests (usuário disponível)', false, 'Nenhum usuário no banco');
}

// ── Relatório ─────────────────────────────────────────────────────────────
echo str_pad('TESTE', 50) . str_pad('RESULTADO', 12) . "DETALHE\n";
echo str_repeat('-', 95) . "\n";

$total = count($results);
$ok = count(array_filter($results, fn ($r) => $r['ok']));

foreach ($results as $r) {
    echo str_pad($r['name'], 50)
        . str_pad($r['ok'] ? 'PASS' : 'FAIL', 12)
        . $r['detail'] . "\n";
}

echo str_repeat('-', 95) . "\n";
echo "Total: {$ok}/{$total} passaram\n";

exit($ok === $total ? 0 : 1);
