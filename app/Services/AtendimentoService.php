<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class AtendimentoService
{
    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService
    ) {}

    public function getAtendimentos(
        ?string $status = null,
        int $limit = 10,
        int $offset = 0,
        ?string $regiao = null,
        ?string $tecnico = null,
        ?string $taskCode = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
    ) {
        $query = OpTask::tarefasPai(OpTask::CATEGORIAS_ATENDIMENTO)
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

    /**
     * Mapeia o status do kanban para os valores legados gravados no banco.
     */
    private function statusParaConsulta(string $status): array
    {
        return match ($status) {
            'Criada' => ['Criada', 'Backlog'],
            'Finalizada' => ['Finalizada', 'Concluída'],
            default => [$status],
        };
    }

    public function normalizarParaExibicao(OpTask $atendimento): array
    {
        $dados = $atendimento->toArray();

        $nome = trim((string) ($dados['nome_cliente'] ?? ''));
        if ($nome === '') {
            $nome = trim((string) ($dados['titulo'] ?? ''));
        }

        $coordenadas = trim((string) ($dados['coordenadas'] ?? ''));
        $descricao = trim((string) ($dados['descricao'] ?? ''));

        if ($coordenadas === '' && $this->pareceCoordenada($descricao)) {
            $coordenadas = $descricao;
            $descricao = '';
        }

        $endereco = trim((string) ($dados['localizacao_texto'] ?? ''));
        if ($endereco === '') {
            $endereco = trim((string) ($dados['setor'] ?? ''));
        }

        $numeroOs = trim((string) ($dados['numero_os'] ?? ''));
        if ($numeroOs === '') {
            $numeroOs = trim((string) ($dados['ordem_servico'] ?? ''));
        }

        $status = trim((string) ($dados['status'] ?? ''));
        $dataEntrada = substr((string) ($atendimento->criadaEm ?? $dados['data_entrada'] ?? ''), 0, 10);

        return array_merge($dados, [
            'nome' => $nome !== '' ? $nome : 'Atendimento',
            'protocolo' => '',
            'sub_processo' => '',
            'data_entrada' => $dataEntrada,
            'data_instalacao' => '',
            'numero_os' => $numeroOs,
            'regiao' => trim((string) ($dados['regiao'] ?? '')),
            'responsavel' => trim((string) ($dados['responsavel'] ?? '')),
            'localizacao_texto' => $endereco,
            'coordenadas' => $coordenadas,
            'descricao' => $descricao,
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

    private function pareceCoordenada(string $valor): bool
    {
        return (bool) preg_match(
            '/^-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?$/',
            trim($valor)
        );
    }

    public function createAtendimento(array $dados): OpTask
    {
        $dados = OpTask::filtrarEntradaCliente($dados);
        $dados['categoria'] = 'atendimento-cliente';
        $dados = $this->sincronizarCamposLegado($dados);

        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '') {
            $dados['titulo'] = 'Atendimento';
        }

        // Data de entrada = momento da criação da tarefa.
        unset($dados['data_instalacao'], $dados['protocolo'], $dados['sub_processo']);
        $dados['data_entrada'] = now()->toDateString();
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    public function updateAtendimento(OpTask $atendimento, array $dados): OpTask
    {
        $statusAnterior = $atendimento->status;
        $dados = OpTask::filtrarEntradaCliente($this->sincronizarCamposLegado($dados));

        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $atendimento->id)
                ->where('status', '!=', 'Finalizada')
                ->count();

            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar o atendimento');
            }
        }

        $atendimento->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $atendimento->fresh();
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

        return $atendimento->fresh();
    }

    public function deleteAtendimento(OpTask $atendimento): void
    {
        OpTask::where('parent_task_id', $atendimento->id)->delete();

        $atendimento->delete();
    }

    private function sincronizarCamposLegado(array $dados): array
    {
        $nome = trim((string) ($dados['nome_cliente'] ?? ''));
        $titulo = trim((string) ($dados['titulo'] ?? ''));

        if ($nome !== '' && $titulo === '') {
            $dados['titulo'] = $nome;
        } elseif ($titulo !== '' && $nome === '') {
            $dados['nome_cliente'] = $titulo;
        }

        // Campos removidos da tela — não sobrescrever com valores vazios no update.
        unset($dados['protocolo'], $dados['sub_processo'], $dados['data_instalacao']);

        // Data de entrada é sempre a data de criação da tarefa.
        if (array_key_exists('data_entrada', $dados)) {
            unset($dados['data_entrada']);
        }

        return $dados;
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
}
