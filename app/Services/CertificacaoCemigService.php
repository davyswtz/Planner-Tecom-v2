<?php

namespace App\Services;

use App\Models\OpTask;

class CertificacaoCemigService
{
    public const CATEGORIA = 'certificacao-cemig';

    public const STATUS_KANBAN = [
        'Pendente',
        'Em andamento',
        'Validação',
        'Precisa de adequação',
        'Concluído',
    ];

    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService,
    ) {
    }

    public function getCertificacoes(
        int $limit = 10,
        int $offset = 0,
        ?string $status = null,
        ?string $regiao = null,
        ?string $tecnico = null,
        ?string $taskCode = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
        ?string $busca = null,
    ) {
        $query = OpTask::tarefasPai(self::CATEGORIA)
            ->orderBy('updated_at', 'desc')
            ->when($status, fn ($q) => $q->whereIn('status', $this->statusParaConsulta($status)))
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->when($tecnico, fn ($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
            ->when($taskCode, fn ($q) => $q->where('taskCode', 'like', "%{$taskCode}%"))
            ->when($dataInicio, fn ($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->whereDate('criadaEm', '<=', $dataFim))
            ->when($busca, function ($q) use ($busca) {
                $termo = '%' . $busca . '%';
                $q->where(function ($sub) use ($termo) {
                    $sub->where('titulo', 'like', $termo)
                        ->orWhere('descricao', 'like', $termo)
                        ->orWhere('protocolo', 'like', $termo);
                });
            });

        if ($status === 'Concluído') {
            return $query->limit(1000)->offset($offset)->get()
                ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
        }

        return $query->limit($limit)->offset($offset)->get()
            ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
    }

    private function statusParaConsulta(string $status): array
    {
        return match ($status) {
            'Pendente' => ['Pendente', 'Criada', 'Backlog'],
            'Concluído' => ['Concluído', 'Finalizada', 'Concluída'],
            default => [$status],
        };
    }

    public function normalizarParaExibicao(OpTask $certificacao): array
    {
        $dados = $certificacao->toArray();
        $status = trim((string) ($dados['status'] ?? ''));

        return array_merge($dados, [
            'status_exibicao' => $this->statusParaExibicao($status),
            'status_kanban' => $this->statusParaKanban($status),
        ]);
    }

    private function statusParaExibicao(string $status): string
    {
        return match ($status) {
            'Criada', 'Backlog' => 'Pendente',
            'Finalizada', 'Concluída' => 'Concluído',
            default => $status,
        };
    }

    private function statusParaKanban(string $status): string
    {
        return $this->statusParaExibicao($status);
    }

    public function createCertificacao(array $dados): OpTask
    {
        $dados['categoria'] = self::CATEGORIA;
        $dados['status'] = $dados['status'] ?? 'Pendente';
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    public function updateCertificacao(OpTask $certificacao, array $dados): OpTask
    {
        $statusAnterior = $certificacao->status;

        if (isset($dados['status']) && $dados['status'] === 'Concluído') {
            $osPendentes = OpTask::where('parent_task_id', $certificacao->id)
                ->where('status', '!=', 'Finalizada')
                ->count();

            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de concluir a certificação');
            }
        }

        $certificacao->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $certificacao->fresh();
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

        return $certificacao->fresh();
    }

    public function deleteCertificacao(OpTask $certificacao): void
    {
        OpTask::where('parent_task_id', $certificacao->id)
            ->where('categoria', 'ordem-servico')
            ->delete();
        $certificacao->delete();
    }
}
