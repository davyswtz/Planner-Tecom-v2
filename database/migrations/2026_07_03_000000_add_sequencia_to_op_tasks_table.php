<?php

use App\Models\OpTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('op_tasks', 'sequencia')) {
            Schema::table('op_tasks', function (Blueprint $table) {
                $table->unsignedInteger('sequencia')->default(0)->after('parent_task_id');
                $table->index(['parent_task_id', 'sequencia']);
            });
        }

        $agrupadas = OpTask::query()
            ->whereNotNull('parent_task_id')
            ->orderBy('parent_task_id')
            ->orderBy('criadaEm')
            ->orderBy('id')
            ->get(['id', 'parent_task_id', 'criadaEm'])
            ->groupBy('parent_task_id');

        foreach ($agrupadas as $tarefas) {
            foreach ($tarefas->values() as $indice => $tarefa) {
                OpTask::query()
                    ->whereKey($tarefa->id)
                    ->update(['sequencia' => $indice + 1]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('op_tasks', 'sequencia')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_task_id', 'sequencia']);
            $table->dropColumn('sequencia');
        });
    }
};
