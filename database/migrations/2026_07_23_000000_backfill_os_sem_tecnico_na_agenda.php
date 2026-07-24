<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('op_tasks as os')
                ->leftJoin('op_tasks as parent', 'parent.id', '=', 'os.parent_task_id')
                ->where('os.categoria', 'ordem-servico')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('os_tecnicos')
                        ->whereColumn('os_tecnicos.task_id', 'os.id');
                })
                ->select([
                    'os.id',
                    'os.parent_task_id',
                    'os.titulo',
                    'os.taskCode',
                    'os.ordem_servico',
                    'os.numero_os',
                    'os.regiao',
                    'os.status',
                    'os.prioridade',
                    'os.criadaEm',
                    'parent.regiao as parent_regiao',
                ])
                ->orderBy('os.id')
                ->each(function ($os): void {
                    $criadaEm = (string) ($os->criadaEm ?? now()->toDateTimeString());

                    DB::table('os_tecnicos')->insert([
                        'task_id' => $os->id,
                        'parent_task_id' => $os->parent_task_id,
                        'tecnico_nome' => '',
                        'ordem_servico' => $os->ordem_servico ?: ($os->numero_os ?? ''),
                        'titulo' => $os->titulo ?? '',
                        'task_code' => $os->taskCode ?? '',
                        'categoria' => 'ordem-servico',
                        'regiao' => $os->regiao ?: ($os->parent_regiao ?? ''),
                        'status' => $os->status ?? '',
                        'prioridade' => $os->prioridade ?? '',
                        'data_criacao' => substr($criadaEm, 0, 10),
                        'data_conclusao' => '',
                        'criada_em' => $criadaEm,
                        'correcao_dados' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        });
    }

    public function down(): void
    {
        // Backfill de dados: não removemos registros que possam ter sido programados depois.
    }
};
