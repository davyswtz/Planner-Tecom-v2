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
        $query = OpTask::rompimentosPai()
        ->orderBy('updated_at', 'desc')
        ->when($status, fn($q) => $q->where('status', $status))
        ->when($regiao, fn($q) => $q->where('regiao', $regiao))
        ->when($tecnico, fn($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
        ->when($taskCode, fn ($q) => $q->buscaTexto($taskCode))
        ->when($dataInicio, fn($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
        ->when($dataFim, fn($q) => $q->whereDate('criadaEm', '<=', $dataFim));

    if ($status === 'Finalizada') {
        return $query->limit(1000)->offset($offset)->get();
    }

    return $query->limit($limit)->offset($offset)->get(); 
    }

    public function createRompimento(array $dados): OpTask
    {
        $dados = OpTask::filtrarEntradaCliente($dados);
        $dados['categoria'] = 'rompimentos';
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
        $dados = OpTask::filtrarEntradaCliente($dados);
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
            $tarefaAtualizada = $rompimento->fresh();
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $tarefaAtualizada->toArray(),
                $statusAnterior,
                $dados['status']
            );
            $googleChatService = $this->googleChatService;
            app()->terminating(function () use ($tarefaAtualizada, $mensagem, $googleChatService, $dados) {
                $googleChatService->enviarNotificacao($tarefaAtualizada, $mensagem, $dados['status']);
            });
        }
        return $rompimento->fresh();
    }

    public function deleteRompimento(OpTask $rompimento): void {
        OpTask::where('parent_task_id', $rompimento->id)->delete();
        $rompimento->delete();
    }

}