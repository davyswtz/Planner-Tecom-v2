<?php

namespace App\Services;
use App\Services\GoogleChatService;
use App\Services\OpTaskService;
use App\Models\OpTask;

class RompimentoService
{
public function __construct(private OpTaskService $opTaskService
, private GoogleChatService $googleChatService){}

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
        $statusAnterior = $rompimento->status;
        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $rompimento->id)
                ->where('status', '!=', 'Finalizada')
                ->count();
            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar o rompimento');
            }
        }
        $rompimento->update($dados);
        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $rompimento->toArray(),
                $statusAnterior,
                $dados['status']
            );
            $this->googleChatService->enviarNotificacao($rompimento, $mensagem);
        }
        return $rompimento->fresh();
    }

    public function deleteRompimento(OpTask $rompimento): void {
        $rompimento->delete();
    }

}