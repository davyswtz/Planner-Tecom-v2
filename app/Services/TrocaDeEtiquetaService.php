<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class TrocaDeEtiquetaService
{
    public const CATEGORIA = 'troca-etiqueta';

    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService,
    ) {}

    public function getTrocaDeEtiqueta(int $limit = 10, int $offset = 0, ?string $status = null, ?string $regiao = null, ?string $tecnico = null, ?string $taskCode = null, ?string $dataInicio = null, ?string $dataFim = null)
    {
        $query = OpTask::tarefasPai(self::CATEGORIA)
            ->orderBy('updated_at', 'desc')
            ->when($status, fn ($q) => $q->whereIn('status', $this->statusParaConsulta($status)))
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->when($tecnico, fn ($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
            ->when($taskCode, fn ($q) => $q->where('taskCode', 'like', "%{$taskCode}%"))
            ->when($dataInicio, fn ($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->whereDate('criadaEm', '<=', $dataFim));

        if ($this->isStatusConcluido($status)) {
            return $query->limit(1000)->offset($offset)->get();
        }

        return $query->limit($limit)->offset($offset)->get();
    }

    private function statusParaConsulta(string $status): array
    {
        return match ($status) {
            'Pendente', 'Criada' => ['Pendente', 'Criada', 'Backlog'],
            'Concluída', 'Finalizada', 'Concluído' => ['Concluída', 'Finalizada', 'Finalizado', 'Concluído'],
            default => [$status],
        };
    }

    private function isStatusConcluido(?string $status): bool
    {
        return in_array($status, ['Concluída', 'Finalizada', 'Concluído'], true);
    }

    public function showTrocaDeEtiqueta(OpTask $opTask): OpTask
    {
        return $opTask;
    }

    public function createTrocaDeEtiqueta(array $dados): OpTask
    {
        $dados['categoria'] = self::CATEGORIA;
        $dados['status'] = $dados['status'] ?? 'Pendente';
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    public function buscarEndereco(string $coordenada): string
    {
        $partes = explode(',', $coordenada);
        $lat = $partes[0];
        $lng = $partes[1];

        $response = Http::withHeaders([
            'User-Agent' => 'Planner/1.0',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
        ]);

        return $response->json('display_name') ?? 'Endereço não encontrado';
    }

    public function updateTrocaDeEtiqueta(OpTask $trocaDeEtiqueta, array $dados): OpTask
    {
        $statusAnterior = $trocaDeEtiqueta->status;

        if (isset($dados['status']) && $this->isStatusConcluido($dados['status'])) {
            $osPendentes = OpTask::where('parent_task_id', $trocaDeEtiqueta->id)
                ->whereNotIn('status', ['Finalizada', 'Finalizado', 'Concluída', 'Concluído'])
                ->count();
            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de concluir a troca de etiqueta');
            }
            $dados['status'] = 'Concluída';
        }

        if (isset($dados['status']) && in_array($dados['status'], ['Criada', 'Backlog'], true)) {
            $dados['status'] = 'Pendente';
        }

        $trocaDeEtiqueta->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $trocaDeEtiqueta->fresh();
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

        return $trocaDeEtiqueta->fresh();
    }

    public function deleteTrocaDeEtiqueta(OpTask $trocaDeEtiqueta): void
    {
        OpTask::where('parent_task_id', $trocaDeEtiqueta->id)
            ->where('categoria', 'ordem-servico')
            ->delete();
        $trocaDeEtiqueta->delete();
    }
}
