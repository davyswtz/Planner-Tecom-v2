<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\OpTask;
use App\Models\OsTecnico;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class CorrecaoDadosService
{
    public function __construct(private OpTaskService $opTaskService) {}

    public function listar(): Collection
    {
        return OpTask::query()
            ->where('correcao_dados', true)
            ->orderByDesc('criadaEm')
            ->get()
            ->map(fn (OpTask $task) => $this->formatar($task));
    }

    public function criar(array $dados): array
    {
        return DB::transaction(function () use ($dados) {
            $registro = $this->registro($dados);
            $categoriaTela = $this->categoriaTela($dados);
            $categoria = $registro === 'os' ? 'ordem-servico' : $categoriaTela;
            $status = trim((string) ($dados['status'] ?? ($registro === 'os' ? 'Aberta' : 'Criada')));
            $dataCriacao = $this->normalizarData($dados['data_criacao'] ?? null, 'data de criação');
            $dataConclusao = ! empty($dados['data_conclusao'])
                ? $this->normalizarData($dados['data_conclusao'], 'data de conclusão')
                : null;

            $tecnico = trim((string) ($dados['tecnico'] ?? ''));
            $regiao = trim((string) ($dados['regiao'] ?? ''));

            $payload = [
                'titulo' => trim((string) $dados['titulo']),
                'descricao' => trim((string) ($dados['descricao'] ?? '')),
                'categoria' => $categoria,
                'sub_processo' => $registro === 'os' ? $categoriaTela : '',
                'regiao' => $regiao,
                'responsavel' => $tecnico,
                'status' => $status,
                'prioridade' => $dados['prioridade'] ?? 'Média',
                'correcao_dados' => true,
                'criadaEm' => $this->dataParaTimestamp($dataCriacao),
                'is_parent_task' => $registro === 'tarefa',
                'parent_task_id' => null,
            ];

            if ($this->statusEhConcluido($status)) {
                $payload['assinada_em'] = $this->dataParaTimestamp($dataConclusao ?? $dataCriacao);
            }

            $payload['taskCode'] = $this->opTaskService->gerarTaskCode($payload);
            $task = OpTask::create($payload);

            if ($registro === 'os') {
                if ($tecnico === '') {
                    throw new InvalidArgumentException('Informe o técnico para a ordem de serviço.');
                }

                $this->sincronizarOsTecnico($task, $tecnico, $dataCriacao, $dataConclusao, $status);
            }

            return $this->formatar($task->fresh());
        });
    }

    public function atualizar(int $id, array $dados): array
    {
        $task = $this->buscarCorrecao($id);

        return DB::transaction(function () use ($task, $dados) {
            $registro = array_key_exists('registro', $dados) || array_key_exists('tipo', $dados)
                ? $this->registro($dados)
                : ($task->categoria === 'ordem-servico' ? 'os' : 'tarefa');
            $categoriaTela = array_key_exists('categoria', $dados)
                ? $this->categoriaTela($dados)
                : ($task->categoria === 'ordem-servico' ? ($task->sub_processo ?: 'rompimentos') : $task->categoria);

            $status = trim((string) ($dados['status'] ?? $task->status));
            $dataCriacao = $this->normalizarData($dados['data_criacao'] ?? substr((string) $task->criadaEm, 0, 10), 'data de criação');
            $dataConclusao = array_key_exists('data_conclusao', $dados) && $dados['data_conclusao'] !== null && $dados['data_conclusao'] !== ''
                ? $this->normalizarData($dados['data_conclusao'], 'data de conclusão')
                : null;

            $tecnico = trim((string) ($dados['tecnico'] ?? $task->responsavel));

            $task->titulo = trim((string) ($dados['titulo'] ?? $task->titulo));
            $task->descricao = trim((string) ($dados['descricao'] ?? $task->descricao));
            $task->categoria = $registro === 'os' ? 'ordem-servico' : $categoriaTela;
            $task->sub_processo = $registro === 'os' ? $categoriaTela : '';
            $task->regiao = trim((string) ($dados['regiao'] ?? $task->regiao));
            $task->responsavel = $tecnico;
            $task->status = $status;
            $task->prioridade = $dados['prioridade'] ?? $task->prioridade;
            $task->criadaEm = $this->dataParaTimestamp($dataCriacao);
            $task->is_parent_task = $registro === 'tarefa';
            $task->parent_task_id = null;

            if ($this->statusEhConcluido($status)) {
                $task->assinada_em = $this->dataParaTimestamp($dataConclusao ?? $dataCriacao);
            } else {
                $task->assinada_em = '';
            }

            $task->save();

            if ($registro === 'os') {
                if ($tecnico === '') {
                    throw new InvalidArgumentException('Informe o técnico para a ordem de serviço.');
                }

                $this->sincronizarOsTecnico($task, $tecnico, $dataCriacao, $dataConclusao, $status);
            } else {
                $this->removerOsTecnicosDaTask((int) $task->id);
            }

            return $this->formatar($task->fresh());
        });
    }

    public function excluir(int $id): void
    {
        $task = $this->buscarCorrecao($id);
        $taskId = (int) $task->id;

        DB::transaction(function () use ($task, $taskId) {
            $this->removerOsTecnicosDaTask($taskId);

            OpTask::query()
                ->where('correcao_dados', true)
                ->where('parent_task_id', $taskId)
                ->each(fn (OpTask $filha) => $this->excluirRegistroInterno((int) $filha->id));

            $this->limparNotificacoes($taskId);

            $excluido = OpTask::query()
                ->whereKey($taskId)
                ->where('correcao_dados', true)
                ->delete();

            if ($excluido === 0) {
                throw new RuntimeException('Não foi possível excluir o registro do banco de dados.');
            }
        });

        if (OpTask::whereKey($taskId)->exists()) {
            throw new RuntimeException('Não foi possível excluir o registro do banco de dados.');
        }
    }

    private function excluirRegistroInterno(int $taskId): void
    {
        $this->removerOsTecnicosDaTask($taskId);
        $this->limparNotificacoes($taskId);
        OpTask::query()->whereKey($taskId)->where('correcao_dados', true)->delete();
    }

    private function removerOsTecnicosDaTask(int $taskId): void
    {
        OsTecnico::query()
            ->where('correcao_dados', true)
            ->where(function ($query) use ($taskId) {
                $query->where('task_id', $taskId)
                    ->orWhere('parent_task_id', $taskId);
            })
            ->delete();
    }

    private function limparNotificacoes(int $taskId): void
    {
        if (! Schema::hasTable('app_notification')) {
            return;
        }

        AppNotification::query()
            ->where('ref_type', 'op_task')
            ->where('ref_id', $taskId)
            ->delete();
    }

    private function buscarCorrecao(int $id): OpTask
    {
        $task = OpTask::query()->find($id);

        if (! $task || ! $task->correcao_dados) {
            throw new InvalidArgumentException('Registro não encontrado ou não foi criado pela correção de dados.');
        }

        return $task;
    }

    private function sincronizarOsTecnico(
        OpTask $task,
        string $tecnico,
        string $dataCriacao,
        ?string $dataConclusao,
        string $status,
    ): void {
        $this->removerOsTecnicosDaTask((int) $task->id);

        OsTecnico::create([
            'task_id' => $task->id,
            'parent_task_id' => null,
            'tecnico_nome' => $tecnico,
            'titulo' => $task->titulo,
            'task_code' => $task->taskCode,
            'categoria' => $task->categoria,
            'regiao' => $task->regiao,
            'status' => $status,
            'prioridade' => $task->prioridade,
            'data_criacao' => $dataCriacao,
            'data_conclusao' => $this->statusEhConcluido($status)
                ? ($dataConclusao ?? $dataCriacao)
                : '',
            'criada_em' => $dataCriacao,
            'correcao_dados' => true,
        ]);
    }

    private function formatar(OpTask $task): array
    {
        $osTecnico = OsTecnico::query()
            ->where('task_id', $task->id)
            ->where('correcao_dados', true)
            ->first();

        $dataCriacao = $osTecnico?->data_criacao?->format('Y-m-d')
            ?? substr((string) $task->criadaEm, 0, 10);

        $dataConclusao = trim((string) ($task->assinada_em ?: ($osTecnico?->data_conclusao ?? '')));
        $dataConclusao = $dataConclusao !== '' ? substr($dataConclusao, 0, 10) : null;

        $categoriaTela = $task->categoria === 'ordem-servico'
            ? (trim((string) $task->sub_processo) ?: 'rompimentos')
            : $task->categoria;

        return [
            'id' => (int) $task->id,
            'registro' => $task->categoria === 'ordem-servico' ? 'os' : 'tarefa',
            'tipo' => $task->categoria === 'ordem-servico' ? 'os' : 'tarefa',
            'categoria' => $categoriaTela,
            'categoria_label' => $this->rotuloCategoriaTela($categoriaTela),
            'titulo' => $task->titulo,
            'taskCode' => $task->taskCode,
            'tecnico' => trim((string) $task->responsavel) ?: ($osTecnico?->tecnico_nome ?? ''),
            'regiao' => $task->regiao,
            'status' => $task->status,
            'prioridade' => $task->prioridade,
            'data_criacao' => $dataCriacao,
            'data_conclusao' => $dataConclusao,
            'descricao' => $task->descricao,
            'correcao_dados' => true,
        ];
    }

    private function normalizarData(?string $data, string $rotulo): string
    {
        $data = trim((string) $data);

        if ($data === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            throw new InvalidArgumentException("Informe uma {$rotulo} válida.");
        }

        return $data;
    }

    private function dataParaTimestamp(string $data): string
    {
        return $data.' 12:00:00';
    }

    private function statusEhConcluido(string $status): bool
    {
        $s = mb_strtolower(trim($status));

        return in_array($s, ['finalizada', 'finalizado', 'concluída', 'concluida', 'concluído', 'concluido'], true);
    }

    private function registro(array $dados): string
    {
        $valor = mb_strtolower(trim((string) ($dados['registro'] ?? $dados['tipo'] ?? '')));

        if (! in_array($valor, ['tarefa', 'os'], true)) {
            throw new InvalidArgumentException('Informe se o registro é tarefa ou O.S.');
        }

        return $valor;
    }

    private function categoriaTela(array $dados): string
    {
        $categoria = trim((string) ($dados['categoria'] ?? ''));

        if ($categoria === '') {
            throw new InvalidArgumentException('Selecione a categoria (tela).');
        }

        if (! in_array($categoria, array_keys($this->categoriasTela()), true)) {
            throw new InvalidArgumentException('Categoria inválida.');
        }

        return $categoria;
    }

    /** @return array<string, string> */
    public function categoriasTela(): array
    {
        return [
            'tarefas' => 'Tarefas',
            'rompimentos' => 'Rompimentos',
            'troca-poste' => 'Troca de poste',
            'troca-etiqueta' => 'Troca de etiqueta',
            'otimizacao-rede' => 'Otimização de rede',
            'atendimento-cliente' => 'Atendimento',
            'manutencao-corretiva' => 'Manutenção',
            'certificacao-cemig' => 'Certificação',
            'correcao-atenuacao' => 'Correção de sinal',
        ];
    }

    private function rotuloCategoriaTela(string $slug): string
    {
        return $this->categoriasTela()[$slug] ?? $slug;
    }
}
