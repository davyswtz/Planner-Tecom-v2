<?php

namespace App\Services;

use App\Exports\OrdemServicoExcelExport;
use App\Models\OpTask;
use App\Models\OsTecnico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdemServicoService
{
    public function __construct(
        private TecnicoNomeResolver $tecnicoNomeResolver,
        private OrdemServicoExcelExport $excelExport,
    ) {}

    public function normalizarStatusOs(?string $status): string
    {
        $s = mb_strtolower(trim((string) $status));

        return match ($s) {
            'aberta', '' => 'Aberta',
            'em andamento', 'em_andamento' => 'Em andamento',
            'finalizada', 'concluída', 'concluida' => 'Finalizada',
            default => $status ? trim($status) : 'Aberta',
        };
    }

    private function contarPorStatus(Collection $items, string $statusNormalizado): int
    {
        return $items->filter(fn (array $item) => $this->normalizarStatusOs($item['status'] ?? '') === $statusNormalizado)->count();
    }

    public function getDashboard(array $filtros = []): array
    {
        $os = $this->listarOsNormalizadas($filtros);
        $osUnicas = $os->unique('id')->values();

        $porTecnico = $os
            ->groupBy('tecnico')
            ->map(function (Collection $items, string $tecnico) {
                $regiao = $items->pluck('tecnico_regiao')->filter()->first()
                  ?: ($items->pluck('regiao')->filter()->countBy()->sortDesc()->keys()->first() ?? '');

                return [
                    'tecnico' => $tecnico,
                    'regiao' => $regiao,
                    'aberta' => $this->contarPorStatus($items, 'Aberta'),
                    'em_andamento' => $this->contarPorStatus($items, 'Em andamento'),
                    'finalizada' => $this->contarPorStatus($items, 'Finalizada'),
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $porRegiao = $osUnicas
            ->groupBy(fn (array $item) => $item['regiao'] ?: 'Sem região')
            ->map(function (Collection $items, string $regiao) {
                return [
                    'regiao' => $regiao,
                    'aberta' => $this->contarPorStatus($items, 'Aberta'),
                    'em_andamento' => $this->contarPorStatus($items, 'Em andamento'),
                    'finalizada' => $this->contarPorStatus($items, 'Finalizada'),
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $porCategoriaPai = $osUnicas
            ->groupBy(fn (array $item) => $this->rotuloCategoriaPai($item['categoria_pai']))
            ->map(function (Collection $items, string $label) {
                return [
                    'categoria' => $label,
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return [
            'totais' => [
                'total' => $osUnicas->count(),
                'aberta' => $this->contarPorStatus($osUnicas, 'Aberta'),
                'em_andamento' => $this->contarPorStatus($osUnicas, 'Em andamento'),
                'finalizada' => $this->contarPorStatus($osUnicas, 'Finalizada'),
                'tecnicos' => $porTecnico->count(),
            ],
            'por_tecnico' => $porTecnico,
            'por_regiao' => $porRegiao,
            'por_categoria_pai' => $porCategoriaPai,
        ];
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $os = $this->listarOsNormalizadas($filtros);

        return [
            'total' => $os->count(),
            'items' => $os->slice($offset, $limit)->values()->all(),
        ];
    }

    public function show(int $id): ?array
    {
        $os = $this->listarOsNormalizadas(['id' => $id]);

        if ($os->isEmpty()) {
            return null;
        }

        $primeira = $os->first();
        $tecnicos = $os->pluck('tecnico')->unique()->values()->all();
        $primeira['tecnico'] = implode(', ', $tecnicos);
        $primeira['tecnicos'] = $tecnicos;

        return $primeira;
    }

    public function exportarPlanilha(array $filtros = []): StreamedResponse
    {
        $dashboard = $this->getDashboard($filtros);
        $filtrosAplicados = $this->descreverFiltrosExportacao($filtros);
        $arquivo = $this->nomeArquivoExportacao($filtros);

        return $this->excelExport->download($dashboard, $filtrosAplicados, $arquivo);
    }

    /** @return list<array{0: string, 1: string}> */
    private function descreverFiltrosExportacao(array $filtros): array
    {
        $linhas = [];
        $rotulos = [
            'busca' => 'Busca',
            'regiao' => 'Região',
            'tecnico' => 'Técnico',
            'status' => 'Status',
            'prioridade' => 'Prioridade',
            'categoriaPai' => 'Origem',
        ];

        foreach ($rotulos as $chave => $rotulo) {
            if (empty($filtros[$chave])) {
                continue;
            }

            $valor = $chave === 'categoriaPai'
              ? $this->rotuloCategoriaPai($filtros[$chave])
              : (string) $filtros[$chave];

            $linhas[] = [$rotulo, $valor];
        }

        if (! empty($filtros['dataInicio']) || ! empty($filtros['dataFim'])) {
            $tipo = ($filtros['tipoData'] ?? 'criacao') === 'conclusao'
              ? 'Data de conclusão'
              : 'Data de criação';
            $de = $filtros['dataInicio'] ?? '…';
            $ate = $filtros['dataFim'] ?? '…';
            $linhas[] = ['Período', "{$tipo}: {$de} até {$ate}"];
        }

        if ($linhas === []) {
            $linhas[] = ['Filtros', 'Nenhum — todos os registros'];
        }

        return $linhas;
    }

    private function nomeArquivoExportacao(array $filtros): string
    {
        $partes = ['ordens-servico'];

        if (! empty($filtros['tecnico'])) {
            $partes[] = preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($filtros['tecnico'])) ?: 'tecnico';
        }

        if (! empty($filtros['dataInicio'])) {
            $partes[] = $filtros['dataInicio'];
        }

        if (! empty($filtros['dataFim'])) {
            $partes[] = $filtros['dataFim'];
        }

        $partes[] = now()->format('His');

        return implode('-', $partes).'.xlsx';
    }

    private function listarOsNormalizadas(array $filtros = []): Collection
    {
        $taskIdsOsTecnicos = OsTecnico::query()
            ->whereNotNull('task_id')
            ->pluck('task_id')
            ->unique();

        $query = OpTask::query()
            ->with('parentTask')
            ->where(function (Builder $q) use ($taskIdsOsTecnicos) {
                $q->where('categoria', 'ordem-servico');
                if ($taskIdsOsTecnicos->isNotEmpty()) {
                    $q->orWhereIn('id', $taskIdsOsTecnicos);
                }
            });

        $this->aplicarFiltrosQuery($query, $filtros);

        $tasks = $query->orderByDesc('updated_at')->get();

        $osTecnicoPorTask = OsTecnico::query()
            ->whereIn('task_id', $tasks->pluck('id'))
            ->get()
            ->groupBy('task_id');

        $parentIds = $tasks
            ->pluck('parent_task_id')
            ->merge($osTecnicoPorTask->flatten(1)->pluck('parent_task_id'))
            ->filter()
            ->unique()
            ->values();

        $parentsPorId = OpTask::query()
            ->whereIn('id', $parentIds)
            ->get()
            ->keyBy('id');

        $normalizadas = $tasks->flatMap(function (OpTask $task) use ($osTecnicoPorTask, $parentsPorId) {
            $registros = $osTecnicoPorTask->get($task->id, collect());
            $registro = $registros->first();
            $parentId = $task->parent_task_id ?: ($registro->parent_task_id ?? null);
            $parentTask = $parentId ? $parentsPorId->get((int) $parentId) : null;
            $tecnicos = $this->resolverTecnicos($task, $registros);

            $base = [
                'id' => (int) $task->id,
                'regiao' => trim((string) $task->regiao) ?: trim((string) ($registro->regiao ?? '')),
                'status' => $this->normalizarStatusOs($task->status ?: ($registro->status ?? '')),
                'status_raw' => $task->status ?: ($registro->status ?? ''),
                'criadaEm' => $task->criadaEm,
                'data_criacao' => $this->resolverDataCriacao($task, $registro),
                'data_conclusao' => $this->resolverDataConclusao($task, $registro),
                'titulo' => trim((string) $task->titulo) ?: trim((string) ($registro->titulo ?? '')),
                'numero_os' => trim((string) ($task->numero_os ?: $task->ordem_servico ?: ($registro->ordem_servico ?? ''))),
                'ordem_servico' => trim((string) ($task->ordem_servico ?: ($registro->ordem_servico ?? ''))),
                'taskCode' => trim((string) $task->taskCode) ?: trim((string) ($registro->task_code ?? '')),
                'categoria' => trim((string) $task->categoria) ?: trim((string) ($registro->categoria ?? '')),
                'categoria_pai' => $parentTask?->categoria ?? trim((string) ($task->sub_processo ?? '')),
                'categoria_pai_label' => $this->rotuloCategoriaPai($parentTask?->categoria ?? trim((string) ($task->sub_processo ?? ''))),
                'task_code_pai' => $parentTask?->taskCode ?? '',
                'prioridade' => trim((string) $task->prioridade) ?: trim((string) ($registro->prioridade ?? '')) ?: 'Média',
                'parent_task_id' => $parentId ? (int) $parentId : null,
                'descricao' => $task->descricao,
                'protocolo' => trim((string) $task->protocolo) ?: trim((string) ($registro->protocolo ?? '')),
                'localizacao_texto' => $task->localizacao_texto,
                'coordenadas' => $task->coordenadas,
                'nome_cliente' => $task->nome_cliente,
                'sub_processo' => $task->sub_processo,
                'data_entrada' => $task->data_entrada,
                'data_instalacao' => $task->data_instalacao,
                'assinada_por' => $task->assinada_por,
                'assinada_em' => $task->assinada_em,
            ];

            return collect($tecnicos)->map(function (string $tecnico) use ($base) {
                $meta = $this->tecnicoNomeResolver->resolverOuOriginal($tecnico);

                return array_merge($base, $meta);
            });
        });

        $somenteIdentificadas = $normalizadas
            ->filter(fn (array $item) => $item['tecnico_identificado'] ?? false)
            ->values();

        return $this->aplicarFiltrosPosQuery($somenteIdentificadas, $filtros);
    }

    private function aplicarFiltrosQuery(Builder $query, array $filtros): void
    {
        if (! empty($filtros['id'])) {
            $query->where('op_tasks.id', (int) $filtros['id']);
        }

        if (! empty($filtros['regiao'])) {
            $regiao = $filtros['regiao'];
            $query->where(function (Builder $q) use ($regiao) {
                $q->where('op_tasks.regiao', $regiao)
                    ->orWhereIn('op_tasks.id', OsTecnico::query()
                        ->select('task_id')
                        ->where('regiao', $regiao)
                        ->whereNotNull('task_id'));
            });
        }

        if (! empty($filtros['prioridade'])) {
            $prioridade = $filtros['prioridade'];
            $query->where(function (Builder $q) use ($prioridade) {
                $q->where('op_tasks.prioridade', $prioridade)
                    ->orWhereIn('op_tasks.id', OsTecnico::query()
                        ->select('task_id')
                        ->where('prioridade', $prioridade)
                        ->whereNotNull('task_id'));
            });
        }

        // Período aplicado em aplicarFiltrosPosQuery (usa data_criacao / data_conclusao unificadas)

        if (! empty($filtros['busca'])) {
            $busca = $filtros['busca'];
            $query->where(function (Builder $q) use ($busca) {
                $q->where('op_tasks.taskCode', 'like', "%{$busca}%")
                    ->orWhere('op_tasks.numero_os', 'like', "%{$busca}%")
                    ->orWhere('op_tasks.ordem_servico', 'like', "%{$busca}%")
                    ->orWhere('op_tasks.titulo', 'like', "%{$busca}%")
                    ->orWhere('op_tasks.responsavel', 'like', "%{$busca}%")
                    ->orWhere('op_tasks.protocolo', 'like', "%{$busca}%")
                    ->orWhereIn('op_tasks.id', OsTecnico::query()
                        ->select('task_id')
                        ->where(function (Builder $sub) use ($busca) {
                            $sub->where('ordem_servico', 'like', "%{$busca}%")
                                ->orWhere('task_code', 'like', "%{$busca}%")
                                ->orWhere('titulo', 'like', "%{$busca}%")
                                ->orWhere('tecnico_nome', 'like', "%{$busca}%")
                                ->orWhere('protocolo', 'like', "%{$busca}%");
                        })
                        ->whereNotNull('task_id'));
            });
        }

        if (! empty($filtros['categoriaPai'])) {
            $categoria = $filtros['categoriaPai'];
            $query->where(function (Builder $q) use ($categoria) {
                $q->whereHas('parentTask', fn (Builder $parent) => $parent->where('categoria', $categoria))
                    ->orWhereIn('op_tasks.id', OsTecnico::query()
                        ->select('task_id')
                        ->whereIn('parent_task_id', OpTask::query()
                            ->select('id')
                            ->where('categoria', $categoria))
                        ->whereNotNull('task_id'));
            });
        }
    }

    private function aplicarFiltrosPosQuery(Collection $items, array $filtros): Collection
    {
        if (! empty($filtros['tecnico'])) {
            $tecnico = mb_strtolower($filtros['tecnico']);
            $items = $items->filter(fn (array $item) => mb_strtolower($item['tecnico']) === $tecnico);
        }

        if (! empty($filtros['status'])) {
            $status = $this->normalizarStatusOs($filtros['status']);
            $items = $items->filter(fn (array $item) => $item['status'] === $status);
        }

        if (! empty($filtros['dataInicio']) || ! empty($filtros['dataFim'])) {
            $campo = ($filtros['tipoData'] ?? 'criacao') === 'conclusao' ? 'data_conclusao' : 'data_criacao';
            $inicio = $filtros['dataInicio'] ?? null;
            $fim = $filtros['dataFim'] ?? null;

            $items = $items->filter(function (array $item) use ($campo, $inicio, $fim) {
                $data = $item[$campo] ?? null;
                if (! $data) {
                    return false;
                }
                if ($inicio && $data < $inicio) {
                    return false;
                }
                if ($fim && $data > $fim) {
                    return false;
                }

                return true;
            });
        }

        return $items->values();
    }

    private function resolverDataCriacao(OpTask $task, ?OsTecnico $registro): ?string
    {
        if ($registro?->data_criacao) {
            return substr((string) $registro->data_criacao, 0, 10);
        }

        $criada = trim((string) $task->criadaEm);
        if ($criada !== '') {
            return substr($criada, 0, 10);
        }

        $criadaOs = trim((string) ($registro->criada_em ?? ''));

        return $criadaOs !== '' ? substr($criadaOs, 0, 10) : null;
    }

    private function resolverDataConclusao(OpTask $task, ?OsTecnico $registro): ?string
    {
        foreach ([$task->assinada_em, $registro?->data_conclusao] as $valor) {
            $data = trim((string) ($valor ?? ''));
            if ($data !== '') {
                return substr($data, 0, 10);
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function resolverTecnicos(OpTask $task, Collection $registrosOsTecnico): array
    {
        $nomesOsTecnico = $registrosOsTecnico
            ->map(fn (OsTecnico $registro) => trim((string) $registro->tecnico_nome))
            ->filter(fn (string $nome) => $nome !== '' && $nome !== '—')
            ->unique()
            ->values()
            ->all();

        $responsavel = trim((string) $task->responsavel);
        $nomesResponsavel = $responsavel !== '' ? OpTask::parseResponsaveis($responsavel) : [];

        $nomes = match (true) {
            count($nomesOsTecnico) > 1 => $nomesOsTecnico,
            count($nomesResponsavel) > 1 => $nomesResponsavel,
            count($nomesOsTecnico) === 1 => $nomesOsTecnico,
            count($nomesResponsavel) === 1 => $nomesResponsavel,
            default => [],
        };

        if ($nomes === []) {
            return ['Sem técnico'];
        }

        return array_values(array_unique($nomes));
    }

    private function rotuloCategoriaPai(?string $categoria): string
    {
        return match ($categoria) {
            'rompimentos', 'rompimento' => 'Rompimentos',
            'troca-poste' => 'Troca de poste',
            'otimizacao-rede', 'otimizacao de rede', 'otimização de rede', 'OTIMIZACAO DE REDE', 'OTIMIZAÇÃO DE REDE' => 'Otimização de rede',
            'atendimento-cliente' => 'Atendimento',
            'manutencao-corretiva' => 'Manutenção',
            'correcao-atenuacao' => 'Correção de sinal',
            'troca-etiqueta' => 'Troca de etiqueta',
            'certificacao-cemig' => 'Certificação',
            'qualidade-potencia' => 'Qualidade de potência',
            '' => 'Sem vínculo',
            default => ucfirst(str_replace('-', ' ', $categoria)),
        };
    }
}
