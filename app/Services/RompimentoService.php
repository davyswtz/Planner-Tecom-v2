<?php

namespace App\Services;


use App\Models\OpTask;

class RompimentoService
{
public function __construct(private OpTaskService $opTaskService){}

    public function getRompimentos()
    {
           return OpTask::whereIn('categoria', ['rompimento', 'rompimentos'])->orderBy('updated_at', 'desc')->get();
    }

    public function createRompimento(array $dados): OpTask
    {
        $dados['categoria'] = 'rompimentos';
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);
        return OpTask::create($dados);

    }

    public function showRompimento(OpTask $opTask): OpTask {
        $dados['categoria'] = 'rompimentos';
        return $opTask;
    }

    public function updateRompimento(OpTask $rompimento, array $dados): OpTask {
        $rompimento->update($dados);
        return $rompimento;
    }

    public function deleteRompimento(OpTask $rompimento): OpTask {
        $rompimento->delete();
        return $rompimento;
    }

}