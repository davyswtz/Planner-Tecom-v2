<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$parents = App\Models\OpTask::query()
    ->whereIn('id', [1997, 1751])
    ->get(['id', 'taskCode', 'categoria', 'titulo', 'status', 'parent_task_id', 'regiao']);

echo "Pais:\n" . json_encode($parents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

$parent = App\Models\OpTask::query()
    ->whereNull('parent_task_id')
    ->where(function ($q) {
        $q->whereRaw('UPPER(taskCode) = ?', ['GV-NET-0153'])
            ->orWhere('id', 153);
    })
    ->whereIn('categoria', [
        'otimizacao-rede', 'otimizacao de rede', 'otimização de rede',
        'OTIMIZACAO DE REDE', 'OTIMIZAÇÃO DE REDE',
    ])
    ->get();

echo "\nPai direto NET-0153:\n" . json_encode($parent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
