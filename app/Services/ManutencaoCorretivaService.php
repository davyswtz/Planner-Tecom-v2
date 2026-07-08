<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class ManutencaoCorretivaService
{
    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService,
    ) {
    }

    public function getManutencoesCorretivas(
        int $limit = 10,
        int $offset = 0,
        ?string $status = null,
        ?string $regiao = null,
        ?string $tecnico = null,
        ?string $taskCode = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
    ) {
        $query = OpTask::tarefasPai(OpTask::CATEGORIAS_MANUTENCAO_CORRETIVA)
            ->orderBy('updated_at', 'desc')
            ->when($status, fn ($q) => $q->whereIn('status', $this->statusParaConsulta($status)))
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->when($tecnico, fn ($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
            ->when($taskCode, fn ($q) => $q->buscaTexto($taskCode))
            ->when($dataInicio, fn ($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->whereDate('criadaEm', '<=', $dataFim));

        if ($status === 'Finalizada') {
            return $query->limit(1000)->offset($offset)->get()
                ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
        }

        return $query->limit($limit)->offset($offset)->get()
            ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
    }

    private function statusParaConsulta(string $status): array
    {
        return match ($status) {
            'Criada' => ['Criada', 'Backlog'],
            'Finalizada' => ['Finalizada', 'Concluída'],
            default => [$status],
        };
    }

    public function normalizarParaExibicao(OpTask $manutencao): array
    {
        $dados = $manutencao->toArray();

        $nome = trim((string) ($dados['nome_cliente'] ?? ''));
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($nome === '') {
            $nome = $titulo;
        }

        $setor = trim((string) ($dados['setor'] ?? ''));
        $elemento = trim((string) ($dados['elemento'] ?? ''));
        if ($elemento === '') {
            $elemento = $setor;
        }
        if ($setor === '') {
            $setor = $elemento;
        }

        $codigoExibicao = trim((string) ($dados['taskCode'] ?? ''));
        if ($codigoExibicao === '' && str_contains($titulo, '·')) {
            $codigoExibicao = trim(explode('·', $titulo, 2)[0]);
        }

        $status = trim((string) ($dados['status'] ?? ''));

        return array_merge($dados, [
            'nome' => $nome,
            'setor' => $setor,
            'elemento' => $elemento,
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'localizacao_texto' => trim((string) ($dados['localizacao_texto'] ?? '')),
            'codigo_exibicao' => $codigoExibicao,
            'status_exibicao' => $this->statusParaExibicao($status),
            'status_kanban' => $this->statusParaKanban($status),
        ]);
    }

    private function statusParaExibicao(string $status): string
    {
        return match ($status) {
            'Backlog' => 'Criada',
            'Concluída' => 'Finalizada',
            default => $status,
        };
    }

    private function statusParaKanban(string $status): string
    {
        return match ($status) {
            'Backlog' => 'Criada',
            'Concluída' => 'Finalizada',
            default => $status,
        };
    }

    public function createManutencaoCorretiva(array $dados): OpTask
    {
        $dados = $this->sincronizarElemento($dados);
        $dados = OpTask::filtrarEntradaCliente($dados);
        $dados['categoria'] = 'manutencao-corretiva';
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    public function buscarEndereco(string $coordenada): string
    {
        $partes = explode(',', $coordenada);
        $lat = $partes[0] ?? '';
        $lng = $partes[1] ?? '';

        $response = Http::withHeaders([
            'User-Agent' => 'Planner/1.0',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
        ]);

        return $response->json('display_name') ?? 'Endereço não encontrado';
    }

    public function updateManutencaoCorretiva(OpTask $manutencao, array $dados): OpTask
    {
        $statusAnterior = $manutencao->status;

        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $manutencao->id)
                ->where('status', '!=', 'Finalizada')
                ->count();

            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar a manutenção corretiva');
            }
        }

        $dados = OpTask::filtrarEntradaCliente($this->sincronizarElemento($dados));
        $manutencao->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $manutencao->fresh();
            $payload = $this->normalizarParaExibicao($tarefaAtualizada);
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $payload,
                $statusAnterior,
                $dados['status']
            );
            $googleChatService = $this->googleChatService;
            app()->terminating(function () use ($tarefaAtualizada, $mensagem, $googleChatService, $dados) {
                $googleChatService->enviarNotificacao($tarefaAtualizada, $mensagem, $dados['status']);
            });
        }

        return $manutencao->fresh();
    }

    public function deleteManutencaoCorretiva(OpTask $manutencao): void
    {
        OpTask::where('parent_task_id', $manutencao->id)
            ->where('categoria', 'ordem-servico')
            ->delete();
        $manutencao->delete();
    }

    /** Elemento é persistido em setor (coluna existente). */
    private function sincronizarElemento(array $dados): array
    {
        $elemento = trim((string) ($dados['elemento'] ?? ''));
        $setor = trim((string) ($dados['setor'] ?? ''));

        if ($elemento !== '') {
            $dados['setor'] = $elemento;
        } elseif ($setor !== '') {
            $dados['setor'] = $setor;
        }

        unset($dados['elemento']);

        return $dados;
    }
}
