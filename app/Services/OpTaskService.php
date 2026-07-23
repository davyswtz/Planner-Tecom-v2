<?php

namespace App\Services;

use App\Events\OpTaskChanged;
use App\Models\AppNotification;
use App\Models\OpTask;
use App\Models\OsTecnico;
use App\Services\GoogleChatService;
use App\Services\MensagemTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OpTaskService
{
    public function __construct(
        private GoogleChatService $googleChatService,
        private OsTecnicoService $osTecnicoService,
    ) {}

    public function getOpTasks(int $limit = 40, string $orderBy = 'updated_at', string $order = 'desc', ?string $categoria = null, ?string $responsavel = null, bool $excluirFinalizadas = false)
    {
        return OpTask::query()
            ->whereNull('parent_task_id')
            ->when($categoria !== null, fn ($query) => $query->where('categoria', $categoria))
            ->when($responsavel !== null && $responsavel !== '', fn ($query) => $query->whereResponsavel($responsavel))
            ->when($excluirFinalizadas, fn ($query) => $query->whereNotIn('status', ['Finalizar', 'Finalizada']))
            ->orderBy($orderBy, $order)
            ->limit($limit)
            ->get();
    }

    public function createOpTask(array $dados): OpTask
    {
        $categoria = trim((string) ($dados['categoria'] ?? ''));
        $permitirParent = $categoria === 'ordem-servico';

        $dados = OpTask::filtrarEntradaCliente($dados, permitirParent: $permitirParent, permitirCategoria: true);
        $dados['categoria'] = $categoria !== '' ? $categoria : ($dados['categoria'] ?? '');

        if (($dados['categoria'] ?? '') === 'ordem-servico' && array_key_exists('responsavel', $dados)) {
            $dados['responsavel'] = OpTask::serializarResponsaveis(
                OpTask::parseResponsaveis($dados['responsavel'] ?? '')
            );
        }

        if (
            ! empty($dados['parent_task_id'])
            && ($dados['categoria'] ?? '') === 'ordem-servico'
            && ! isset($dados['sequencia'])
        ) {
            $dados['sequencia'] = $this->proximaSequenciaOs((int) $dados['parent_task_id']);
        }

        $taskCode = $this->gerarTaskCode($dados);
        $task = new OpTask;
        $task->fill($dados);
        $task->taskCode = $taskCode;
        $task->criadaEm = now();
        $task->save();

        if (! empty($dados['parent_task_id'])) {
            OpTask::where('id', $dados['parent_task_id'])->update(['is_parent_task' => true]);
        }

        if (($task->categoria ?? '') === 'ordem-servico') {
            $this->osTecnicoService->sincronizarParaOs($task->fresh());
        }

        $status = trim((string) ($task->status ?? ''));
        if ($this->deveDispararWebhookNaCriacao($status)) {
            $this->dispararWebhookMudancaStatus($task->fresh(), '', $status);
        }

        return $task;
    }

    public function createTarefa(array $dados): OpTask
    {
        return $this->createOpTask([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? '',
            'responsavel' => OpTask::serializarResponsaveis(
                OpTask::parseResponsaveis($dados['responsavel'] ?? '')
            ),
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

        if (array_key_exists('responsavel', $filtrados)) {
            $filtrados['responsavel'] = OpTask::serializarResponsaveis(
                OpTask::parseResponsaveis($filtrados['responsavel'])
            );
        }

        return $this->updateOpTask($opTask, $filtrados);
    }

    public function deleteTarefa(OpTask $opTask): OpTask
    {
        if ($opTask->categoria !== 'tarefas') {
            throw new \InvalidArgumentException(
                'Somente tarefas da categoria "tarefas" podem ser excluídas por este método.'
            );
        }

        $id = (int) $opTask->id;
        $broadcastEvent = OpTaskChanged::fromTask($opTask, 'deleted');

        DB::transaction(function () use ($id): void {
            if (Schema::hasTable('app_notification')) {
                AppNotification::query()
                    ->where('ref_type', 'op_task')
                    ->where('ref_id', $id)
                    ->delete();
            }

            if (Schema::hasTable('op_task_image')) {
                DB::table('op_task_image')->where('op_task_id', $id)->delete();
            }

            OsTecnico::where('parent_task_id', $id)->delete();
            OpTask::where('parent_task_id', $id)->delete();

            $deleted = OpTask::withoutEvents(
                fn () => OpTask::query()
                    ->whereKey($id)
                    ->where('categoria', 'tarefas')
                    ->delete()
            );

            if ($deleted === 0) {
                throw new \RuntimeException('Não foi possível excluir a tarefa do banco de dados.');
            }
        });

        if (OpTask::whereKey($id)->exists()) {
            throw new \RuntimeException('Não foi possível excluir a tarefa do banco de dados.');
        }

        dispatch(static function () use ($broadcastEvent): void {
            try {
                event($broadcastEvent);
            } catch (Throwable $e) {
                Log::warning('Falha ao transmitir exclusão em tempo real.', [
                    'task_id' => $broadcastEvent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();

        return $opTask;
    }

    public function showOpTask(OpTask $opTask){
        return $opTask;
    }

    public function listarOsVinculadas(int $parentId)
    {
        $parent = OpTask::find($parentId);
        $parentCategoria = $parent?->categoria;

        $taskIdsFromOsTecnicos = OsTecnico::where('parent_task_id', $parentId)
            ->pluck('task_id');

        return OpTask::where('parent_task_id', $parentId)
            ->where(function ($query) use ($taskIdsFromOsTecnicos, $parentCategoria) {
                $query->where('categoria', 'ordem-servico');
                if ($parentCategoria) {
                    $query->orWhere('categoria', $parentCategoria);
                }
                if ($taskIdsFromOsTecnicos->isNotEmpty()) {
                    $query->orWhereIn('id', $taskIdsFromOsTecnicos);
                }
            })
            ->orderBy('sequencia')
            ->orderBy('criadaEm')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function reordenarOsVinculadas(int $parentId, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            throw new \InvalidArgumentException('Informe a nova sequência das ordens de serviço.');
        }

        if (in_array(0, $ids, true)) {
            throw new \InvalidArgumentException('Identificadores de OS inválidos.');
        }

        $vinculadasIds = $this->listarOsVinculadas($parentId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($vinculadasIds);
        $idsOrdenados = $ids;
        sort($idsOrdenados);

        if ($vinculadasIds !== $idsOrdenados) {
            throw new \InvalidArgumentException('A sequência enviada não corresponde às ordens de serviço vinculadas.');
        }

        DB::transaction(function () use ($ids, $parentId): void {
            foreach ($ids as $indice => $id) {
                OpTask::query()
                    ->whereKey($id)
                    ->where('parent_task_id', $parentId)
                    ->update(['sequencia' => $indice + 1]);
            }
        });
    }

    private function proximaSequenciaOs(int $parentId): int
    {
        $max = (int) OpTask::query()
            ->where('parent_task_id', $parentId)
            ->max('sequencia');

        return $max + 1;
    }

    public function updateOpTask(OpTask $opTask, array $dados): OpTask
    {
        $statusAnterior = $opTask->status;

        // Atualização via API nunca pode alterar categoria, parent, taskCode, etc.
        $dados = OpTask::filtrarEntradaCliente($dados, permitirParent: false, permitirCategoria: false);

        if (($opTask->categoria ?? '') === 'ordem-servico' && array_key_exists('responsavel', $dados)) {
            $dados['responsavel'] = OpTask::serializarResponsaveis(
                OpTask::parseResponsaveis($dados['responsavel'] ?? '')
            );
        }

        $opTask->update($dados);

        if (isset($dados['status']) && $dados['status'] !== $statusAnterior && ($opTask->categoria ?? '') !== 'tarefas') {
            $this->dispararWebhookMudancaStatus($opTask->fresh(), $statusAnterior, $dados['status']);
        }

        if (($opTask->categoria ?? '') === 'ordem-servico') {
            $this->osTecnicoService->sincronizarParaOs($opTask->fresh());
        }

        return $opTask->fresh();
    }

    private function dispararWebhookMudancaStatus(OpTask $task, string $statusAnterior, string $statusNovo): void
    {
        if (($task->categoria ?? '') === 'tarefas') {
            return;
        }

        $payload = $this->montarPayloadWebhook($task);
        $isOs = ($task->categoria ?? '') === 'ordem-servico';

        if ($isOs && $this->googleChatService->isOsEmAndamento($statusNovo)) {
            $mensagem = $this->googleChatService->montarMensagemOsEmAndamento($payload);
        } elseif ($isOs && $this->googleChatService->isOsFinalizada($statusNovo)) {
            $mensagem = $this->googleChatService->montarMensagemOsFinalizada($payload);
        } else {
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $payload,
                $statusAnterior,
                $statusNovo
            );
        }

        $destinoId = ! empty($task->parent_task_id)
            ? (int) $task->parent_task_id
            : (int) $task->id;

        $osTaskId = $isOs ? (int) $task->id : null;
        $googleChatService = $this->googleChatService;

        app()->terminating(function () use ($destinoId, $mensagem, $googleChatService, $statusNovo, $osTaskId): void {
            $destino = OpTask::find($destinoId)?->fresh();
            if (! $destino) {
                return;
            }

            // Anexos só na OS "Em andamento" — Finalizada não reenvia fotos.
            if ($osTaskId && $googleChatService->isOsEmAndamento($statusNovo)) {
                $os = OpTask::find($osTaskId)?->fresh();
                if ($os) {
                    $mensagem = $googleChatService->enriquecerMensagemComAnexosOs($os, $mensagem);
                }
            }

            $googleChatService->enviarNotificacao($destino, $mensagem, $statusNovo);
        });
    }

    /** @return array<string, mixed> */
    private function montarPayloadWebhook(OpTask $task): array
    {
        $payload = $task->toArray();
        $pai = $task->parent_task_id ? OpTask::find($task->parent_task_id)?->fresh() : null;

        if ($pai) {
            $categoriaPai = app(MensagemTemplateService::class)->normalizarCategoria($pai->categoria ?? '');
            $payload['parent_task_code'] = $pai->taskCode;
            $payload['parent_titulo'] = $pai->titulo;
            $payload['parent_categoria'] = $categoriaPai;
            $payload['parent_categoria_label'] = config("mensagens.categorias.{$categoriaPai}.label") ?? $pai->categoria;

            if (trim((string) ($payload['regiao'] ?? '')) === '') {
                $payload['regiao'] = $pai->regiao;
            }
        }

        $titulo = trim((string) ($payload['titulo'] ?? ''));
        if (preg_match('/^OS\s*[—\-]\s*(.+)$/iu', $titulo, $matches)) {
            $payload['os_tipo'] = trim($matches[1]);
        } else {
            $payload['os_tipo'] = $titulo !== '' ? $titulo : '—';
        }

        return $payload;
    }

    private function deveDispararWebhookNaCriacao(string $status): bool
    {
        return $status !== '' && ! in_array($status, ['Aberta', 'Criada', 'Pendente'], true);
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
        'Caratinga' => 'VL', // legado: Caratinga integra Vale do Aço
        'caratinga' => 'VL',
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
        'ordem-servico'         => 'OS',
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
