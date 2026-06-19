<?php

namespace App\Services;
use App\Services\GoogleChatService;
use App\Services\OpTaskService;
use App\Models\OpTask;


class RompimentoService
{
public function __construct(private OpTaskService $opTaskService
, private GoogleChatService $googleChatService){}

    public function getRompimentos(
        string $status = null,
        int $limit = 10,
        int $offset = 0,
        string $regiao = null,
        string $tecnico = null,
        string $taskCode = null,
        string $dataInicio = null,
        string $dataFim = null,
    )
    {
        $query = OpTask::whereIn('categoria', ['rompimento', 'rompimentos'])
        ->orderBy('updated_at', 'desc')
        ->when($status, fn($q) => $q->where('status', $status))
        ->when($regiao, fn($q) => $q->where('regiao', $regiao))
        ->when($tecnico, fn($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
        ->when($taskCode, fn($q) => $q->where('taskCode', $taskCode))
        ->when($dataInicio, fn($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
        ->when($dataFim, fn($q) => $q->whereDate('criadaEm', '<=', $dataFim));

    if ($status === 'Finalizada') {
        return $query->limit(1000)->offset($offset)->get();
    }

    return $query->limit($limit)->offset($offset)->get(); 
    }

    public function createRompimento(array $dados): OpTask
    {
        $dados['categoria'] = 'rompimentos';
        $dados['criadaEm'] = $dados['criadaEm'] ?? now()->toIso8601String();
        $dados['responsavel'] = trim((string) ($dados['responsavel'] ?? ''));
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
            $googleChatService = $this->googleChatService;
            app()->terminating(function() use ($rompimento, $mensagem, $googleChatService) {
                $googleChatService->enviarNotificacao($rompimento, $mensagem);
            });
        }
        return $rompimento->fresh();
    }

    public function deleteRompimento(OpTask $rompimento): void {
        $rompimento->delete();
    }

}