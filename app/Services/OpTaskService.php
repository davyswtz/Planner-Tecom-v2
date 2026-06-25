<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OsTecnico;
use App\Services\GoogleChatService;

class OpTaskService
{

public function __construct(private GoogleChatService $googleChatService){}

    public function getOpTasks(int $limit = 40, string $orderBy = 'updated_at', string $order = 'desc', ?string $categoria = null, ?string $responsavel = null, bool $excluirFinalizadas = false)
    {
        return OpTask::query()
            ->whereNull('parent_task_id')
            ->when($categoria !== null, fn ($query) => $query->where('categoria', $categoria))
            ->when($responsavel !== null && $responsavel !== '', fn ($query) => $query->where('responsavel', $responsavel))
            ->when($excluirFinalizadas, fn ($query) => $query->whereNotIn('status', ['Finalizar', 'Finalizada']))
            ->orderBy($orderBy, $order)
            ->limit($limit)
            ->get();
    }

    public function createOpTask(array $dados): OpTask{
        $dados['taskCode'] = $this->gerarTaskCode($dados);
        $dados['criadaEm'] = $dados['criadaEm'] ?? now()->toIso8601String();
        $task = OpTask::create($dados);

        if(!empty($dados['parent_task_id'])) {
            OpTask::where('id', $dados['parent_task_id'])->update(['is_parent_task' => true]);

        }
        return $task;
    }

    public function createTarefa(array $dados): OpTask
    {
        return $this->createOpTask([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? '',
            'responsavel' => $dados['responsavel'] ?? '',
            'prazo' => $dados['prazo'] ?? null,
            'prioridade' => $dados['prioridade'] ?? 'Média',
            'categoria' => 'tarefas',
            'status' => $dados['status'] ?? 'Criada',
            'regiao' => $dados['regiao'] ?? '',
        ]);
    }

    public function updateTarefa(OpTask $opTask, array $dados): OpTask
    {
        if ($opTask->categoria !== 'tarefas') {
            throw new \InvalidArgumentException(
                'Somente tarefas da categoria "tarefas" podem ser editadas por este método.'
            );
        }

        $permitidos = ['titulo', 'descricao', 'responsavel', 'prazo', 'prioridade', 'status'];
        $filtrados = array_intersect_key($dados, array_flip($permitidos));

        return $this->updateOpTask($opTask, $filtrados);
    }

    public function deleteTarefa(OpTask $opTask): OpTask
    {
        if ($opTask->categoria !== 'tarefas') {
            throw new \InvalidArgumentException(
                'Somente tarefas da categoria "tarefas" podem ser excluídas por este método.'
            );
        }

        $opTask->delete();

        return $opTask;
    }

    public function showOpTask(OpTask $opTask){
        return $opTask;
    }

    public function listarOsVinculadas(int $parentId)
    {
        $taskIdsFromOsTecnicos = OsTecnico::where('parent_task_id', $parentId)
            ->pluck('task_id');

        return OpTask::where('parent_task_id', $parentId)
            ->where(function ($query) use ($taskIdsFromOsTecnicos) {
                $query->where('categoria', 'ordem-servico');
                if ($taskIdsFromOsTecnicos->isNotEmpty()) {
                    $query->orWhereIn('id', $taskIdsFromOsTecnicos);
                }
            })
            ->orderBy('criadaEm', 'desc')
            ->get();
    }

    public function updateOpTask(OpTask $opTask, array $dados): OpTask
{
    $statusAnterior = $opTask->status;
    $opTask->update($dados);

    if (isset($dados['status']) && $dados['status'] !== $statusAnterior) {
        $isOs = ($opTask->categoria ?? '') === 'ordem-servico';
        $statusNovo = $dados['status'] ?? '';

        if ($isOs && $this->googleChatService->isOsEmAndamento($statusNovo)) {
            $mensagem = $this->googleChatService->montarMensagemOsEmAndamento($opTask->toArray());
        } elseif ($isOs && $this->googleChatService->isOsFinalizada($statusNovo)) {
            $mensagem = $this->googleChatService->montarMensagemOsFinalizada($opTask->toArray());
        } else {
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $opTask->toArray(),
                $statusAnterior,
                $dados['status']
            );
        }

        $googleChatService = $this->googleChatService;

        if (!empty($opTask->parent_task_id)) {
            $pai = OpTask::find($opTask->parent_task_id);
            if ($pai) {
                app()->terminating(function () use ($pai, $mensagem, $googleChatService, $dados) {
                    $googleChatService->enviarNotificacao($pai, $mensagem, $dados['status']);
                });
            }
        } else {
            app()->terminating(function () use ($opTask, $mensagem, $googleChatService, $dados) {
                $googleChatService->enviarNotificacao($opTask, $mensagem, $dados['status']);
            });
        }
    }

    return $opTask->fresh();
}

    /**
     * Remove uma Ordem de Serviço do banco.
     *
     * Regra de negócio: somente OpTasks com categoria "ordem-servico" podem ser
     * excluídas por este método — evita apagar rompimentos/trocas de poste
     * acidentalmente via endpoint genérico /api/op-tasks/{id}.
     *
     * @throws \InvalidArgumentException quando a tarefa não é uma OS
     */
    public function deleteOpTask(OpTask $opTask): OpTask
    {
        if ($opTask->categoria !== 'ordem-servico') {
            throw new \InvalidArgumentException(
                'Somente ordens de serviço podem ser excluídas por este endpoint.'
            );
        }

        $opTask->delete();

        return $opTask;
    }

    private array $regioes = [
        'Goval' => 'GV',
        'goval' => 'GV',
        'Vale do Aço' => 'VL',
        'vale do aço' => 'VL',
        'vale do aco' => 'VL',
        'Caratinga' => 'CA',
        'caratinga' => 'CA',
    ];

    private array $categorias = [
        'rompimentos'           => 'ROM',
        'troca-poste'           => 'TRO',
        'troca de poste'        => 'TRO',
        'otimizacao-rede'       => 'OTM',
        'otimização de rede'    => 'OTM',
        'certificacao-cemig'    => 'CER',
        'certificação cemig'    => 'CER',
        'atendimento-cliente'   => 'ATD',
        'atendimento ao cliente'=> 'ATD',
        'manutencao-corretiva'  => 'MAN',
        'manutenção corretiva'  => 'MAN',
        'correcao-atenuacao'    => 'COR',
        'correção de atenuação' => 'COR',
        'troca-etiqueta'        => 'ETQ',
        'troca de etiqueta'     => 'ETQ',
        'qualidade-potencia'    => 'QUA',
        'qualidade de potencia' => 'QUA',
        'tarefas'               => 'TAR',
        'sem-categoria'         => 'GEN',
    ];



    public function gerarTaskCode(array $dados): string
{
    $regiao = $this->normalizarChaveRegiao($dados['regiao'] ?? '');
    $siglaRegiao = $this->regioes[$regiao] ?? 'XX';
    $categoria = $this->normalizarChaveCategoria($dados['categoria'] ?? '');
    $siglaCategoria = $this->categorias[$categoria] ?? 'GEN';
    $prefixo = $siglaRegiao . '-' . $siglaCategoria;
    $ultimo = OpTask::where('taskCode', 'like', $prefixo . '-%')
        ->orderBy('id', 'desc')
        ->value('taskCode');
    if ($ultimo) {
        $numero = (int) substr($ultimo, strrpos($ultimo, '-') + 1);
        $numero++;
    } else {
        $numero = 1;
    }
    return $prefixo . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
}

    private function normalizarChaveRegiao(string $regiao): string
    {
        $valor = mb_strtolower(trim($regiao));
        $valor = str_replace(['á', 'ã', 'â', 'à'], 'a', $valor);
        $valor = str_replace('ç', 'c', $valor);

        return $valor;
    }

    private function normalizarChaveCategoria(string $categoria): string
    {
        return mb_strtolower(trim($categoria));
    }
}
