<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class CorrecaoDeSinalService
{
    private const CATEGORIA = 'correcao-atenuacao';

    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService,
    ) {
    }

    public function getCorrecoesDeSinal(
        int $limit = 10,
        int $offset = 0,
        ?string $status = null,
        ?string $regiao = null,
        ?string $tecnico = null,
        ?string $taskCode = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
    ) {
        $query = OpTask::tarefasPai(self::CATEGORIA)
            ->orderBy('updated_at', 'desc')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->when($tecnico, fn ($q) => $q->where('responsavel', 'like', "%{$tecnico}%"))
            ->when($taskCode, fn ($q) => $q->where('taskCode', $taskCode))
            ->when($dataInicio, fn ($q) => $q->whereDate('criadaEm', '>=', $dataInicio))
            ->when($dataFim, fn ($q) => $q->whereDate('criadaEm', '<=', $dataFim));

        if ($status === 'Finalizada') {
            return $query->limit(1000)->offset($offset)->get();
        }

        return $query->limit($limit)->offset($offset)->get();
    }

    public function createCorrecaoDeSinal(array $dados): OpTask
    {
        $dados['categoria'] = self::CATEGORIA;
        $dados['taskCode'] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    /**
     * Cria tarefa a partir de um cliente com sinal ruim na busca de caixa.
     */
    public function createFromClienteCaixa(array $dados): OpTask
    {
        $nome = trim((string) ($dados['nome_cliente'] ?? 'Cliente'));
        $caixa = trim((string) ($dados['caixa'] ?? ''));
        $porta = $dados['porta'] ?? '—';
        $serial = trim((string) ($dados['serial'] ?? '—'));
        $rx = $dados['sinal_rx'] ?? null;
        $rxTexto = is_numeric($rx) ? number_format((float) $rx, 2) . ' dBm' : (string) $rx;
        $codigo = (string) ($dados['codigo_cliente'] ?? '');
        $idServico = (string) ($dados['id_cliente_servico'] ?? '');

        $descricao = implode("\n", array_filter([
            "Cliente: {$nome}" . ($codigo !== '' ? " (#{$codigo})" : ''),
            $caixa !== '' ? "Caixa: {$caixa}" : null,
            "Porta: {$porta}",
            "Serial: {$serial}",
            "Sinal RX: {$rxTexto}",
            $idServico !== '' ? "ID serviço Nicon: {$idServico}" : null,
        ]));

        $prioridade = 'Média';
        if (is_numeric($rx) && (float) $rx < -27) {
            $prioridade = 'Alta';
        }

        return $this->createCorrecaoDeSinal([
            'titulo' => "Correção sinal — {$nome}",
            'nome_cliente' => $nome,
            'setor' => $caixa,
            'regiao' => trim((string) ($dados['regiao'] ?? 'Governador Valadares')),
            'status' => 'Criada',
            'prioridade' => $prioridade,
            'descricao' => $descricao,
            'localizacao_texto' => $caixa,
            'coordenadas' => '',
        ]);
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

    public function updateCorrecaoDeSinal(OpTask $correcao, array $dados): OpTask
    {
        $statusAnterior = $correcao->status;

        if (isset($dados['status']) && $dados['status'] === 'Finalizada') {
            $osPendentes = OpTask::where('parent_task_id', $correcao->id)
                ->where('status', '!=', 'Finalizada')
                ->count();

            if ($osPendentes > 0) {
                abort(422, 'Finalize todas as OS antes de finalizar a correção de sinal');
            }
        }

        $correcao->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
            $tarefaAtualizada = $correcao->fresh();
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

        return $correcao->fresh();
    }

    public function deleteCorrecaoDeSinal(OpTask $correcao): void
    {
        OpTask::where('parent_task_id', $correcao->id)
            ->where('categoria', 'ordem-servico')
            ->delete();
        $correcao->delete();
    }
}
