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
            ->when($categoria, fn ($query) => $query->where('categoria', $categoria))
            ->when($responsavel, fn ($query) => $query->where('responsavel', $responsavel))
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

        $permitidos = ['titulo', 'descricao', 'responsavel', 'prazo', 'status'];
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
        $mensagem = $this->googleChatService->montarMensagemStatus(
            $opTask->toArray(),
            $statusAnterior,
            $dados['status']
        );
    
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
        'Vale do Aço' => 'VA',
        'vale do aco' => 'VA',
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
        'atendimento-cliente'   => 'ATE',
        'atendimento ao cliente'=> 'ATE',
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
    $regiao = strtolower(trim($dados['regiao'] ?? ''));
    $siglaRegiao = $this->regioes[$regiao] ?? 'XX';
    $categoria = strtolower(trim($dados['categoria'] ?? ''));
    $siglaCategoria = $this->categorias[$categoria] ?? 'GV';
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
}
