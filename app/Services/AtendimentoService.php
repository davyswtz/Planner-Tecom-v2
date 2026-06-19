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
        $query = OpTask::tarefasPai('atendimento-cliente')
            ->orderBy('updated_at', 'desc')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->when($tecnico, fn ($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
            ->when($taskCode, fn ($q) => $q->where('taskCode', $taskCode))
            ->when($dataInicio, fn ($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->whereDate('criadaEm', '<=', $dataFim));

        if ($status === 'Finalizada') {
            return $query->limit(1000)->offset($offset)->get()
                ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
        }

        return $query->limit($limit)->offset($offset)->get()
            ->map(fn (OpTask $item) => $this->normalizarParaExibicao($item));
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

        $subProcesso = trim((string) ($dados['sub_processo'] ?? ''));
        if ($subProcesso === 'Prazo') {
            $subProcesso = $atendimento->prazo
                ? $atendimento->prazo->format('d/m/Y')
                : '';
        }

        $endereco = trim((string) ($dados['localizacao_texto'] ?? ''));
        if ($endereco === '') {
            $endereco = trim((string) ($dados['setor'] ?? ''));
        }

        $numeroOs = trim((string) ($dados['numero_os'] ?? ''));
        if ($numeroOs === '') {
            $numeroOs = trim((string) ($dados['ordem_servico'] ?? ''));
        }

        return array_merge($dados, [
            'nome' => $nome,
            'protocolo' => trim((string) ($dados['protocolo'] ?? '')),
            'sub_processo' => $subProcesso,
            'numero_os' => $numeroOs,
            'regiao' => trim((string) ($dados['regiao'] ?? '')),
            'responsavel' => trim((string) ($dados['responsavel'] ?? '')),
            'localizacao_texto' => $endereco,
            'coordenadas' => $coordenadas,
            'descricao' => $descricao,
        ]);
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
        $dados['categoria'] = 'atendimento-cliente';
        $dados = $this->sincronizarCamposLegado($dados);
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    public function updateAtendimento(OpTask $atendimento, array $dados): OpTask
    {
        $statusAnterior = $atendimento->status;

        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $atendimento->id)
                ->where('status', '!=', 'Finalizada')
                ->count();

            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar o atendimento');
            }
        }

        $atendimento->update($this->sincronizarCamposLegado($dados));

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $atendimento->fresh();
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $tarefaAtualizada->toArray(),
                $statusAnterior,
                $dados['status']
            );
            $googleChatService = $this->googleChatService;
            app()->terminating(function () use ($tarefaAtualizada, $mensagem, $googleChatService) {
                $googleChatService->enviarNotificacao($tarefaAtualizada, $mensagem);
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
