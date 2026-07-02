<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OsTecnico;

class OsTecnicoService
{
    public function sincronizarParaOs(OpTask $os): void
    {
        if (($os->categoria ?? '') !== 'ordem-servico') {
            return;
        }

        $tecnicos = OpTask::parseResponsaveis($os->responsavel);

        OsTecnico::query()
            ->where('task_id', $os->id)
            ->where(function ($query) {
                $query->where('correcao_dados', false)
                    ->orWhereNull('correcao_dados');
            })
            ->delete();

        if ($tecnicos === []) {
            return;
        }

        $parent = $os->parent_task_id ? OpTask::find($os->parent_task_id) : null;
        $regiao = trim((string) $os->regiao) ?: trim((string) ($parent->regiao ?? ''));
        $criadaEm = (string) ($os->criadaEm ?? now()->toIso8601String());
        $dataCriacao = substr($criadaEm, 0, 10);
        $dataConclusao = $this->statusEhConcluido((string) ($os->status ?? ''))
            ? substr((string) ($os->assinada_em ?? $dataCriacao), 0, 10)
            : '';

        foreach ($tecnicos as $tecnico) {
            OsTecnico::create([
                'task_id' => $os->id,
                'parent_task_id' => $os->parent_task_id,
                'tecnico_nome' => $tecnico,
                'titulo' => $os->titulo ?? '',
                'task_code' => $os->taskCode ?? '',
                'categoria' => $os->categoria ?? 'ordem-servico',
                'regiao' => $regiao,
                'status' => $os->status ?? '',
                'prioridade' => $os->prioridade ?? 'Média',
                'data_criacao' => $dataCriacao !== '' ? $dataCriacao : null,
                'data_conclusao' => $dataConclusao,
                'criada_em' => $criadaEm,
                'correcao_dados' => false,
            ]);
        }
    }

    private function statusEhConcluido(string $status): bool
    {
        $normalizado = mb_strtolower(trim($status));

        return in_array($normalizado, [
            'finalizada',
            'finalizar',
            'concluída',
            'concluida',
            'fechada',
        ], true);
    }
}
