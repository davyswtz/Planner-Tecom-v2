<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OsTecnico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrdemServicoService
{
  public function normalizarStatusOs(?string $status): string
  {
    $s = mb_strtolower(trim((string) $status));

    return match ($s) {
      'aberta', '' => 'Aberta',
      'em andamento', 'em_andamento' => 'Em andamento',
      'finalizada' => 'Finalizada',
      default => $status ? trim($status) : 'Aberta',
    };
  }

  public function getDashboard(array $filtros = []): array
  {
    $os = $this->listarOsNormalizadas($filtros);

    $porTecnico = $os
      ->groupBy('tecnico')
      ->map(function (Collection $items, string $tecnico) {
        $regiao = $items->pluck('regiao')->filter()->countBy()->sortDesc()->keys()->first() ?? '';

        return [
          'tecnico' => $tecnico,
          'regiao' => $regiao,
          'aberta' => $items->where('status', 'Aberta')->count(),
          'em_andamento' => $items->where('status', 'Em andamento')->count(),
          'finalizada' => $items->where('status', 'Finalizada')->count(),
          'total' => $items->count(),
        ];
      })
      ->sortByDesc('total')
      ->values();

    $porRegiao = $os
      ->groupBy(fn (array $item) => $item['regiao'] ?: 'Sem região')
      ->map(function (Collection $items, string $regiao) {
        return [
          'regiao' => $regiao,
          'aberta' => $items->where('status', 'Aberta')->count(),
          'em_andamento' => $items->where('status', 'Em andamento')->count(),
          'finalizada' => $items->where('status', 'Finalizada')->count(),
          'total' => $items->count(),
        ];
      })
      ->sortByDesc('total')
      ->values();

    $porCategoriaPai = $os
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
        'total' => $os->count(),
        'aberta' => $os->where('status', 'Aberta')->count(),
        'em_andamento' => $os->where('status', 'Em andamento')->count(),
        'finalizada' => $os->where('status', 'Finalizada')->count(),
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
    $os = $this->listarOsNormalizadas(['id' => $id])->first();

    return $os;
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
      ->groupBy('task_id')
      ->map(fn (Collection $rows) => $rows->first());

    $normalizadas = $tasks->map(function (OpTask $task) use ($osTecnicoPorTask) {
      $registro = $osTecnicoPorTask->get($task->id);

      return [
        'id' => (int) $task->id,
        'tecnico' => $this->resolverNomeTecnico($task, $registro),
        'regiao' => trim((string) $task->regiao) ?: trim((string) ($registro->regiao ?? '')),
        'status' => $this->normalizarStatusOs($task->status ?: ($registro->status ?? '')),
        'status_raw' => $task->status ?: ($registro->status ?? ''),
        'criadaEm' => $task->criadaEm,
        'data_criacao' => $this->resolverDataCriacao($task, $registro),
        'data_conclusao' => $this->resolverDataConclusao($task, $registro),
        'titulo' => $task->titulo,
        'numero_os' => trim((string) ($task->numero_os ?: $task->ordem_servico ?: ($registro->ordem_servico ?? ''))),
        'taskCode' => $task->taskCode,
        'categoria_pai' => $task->parentTask?->categoria ?? '',
        'categoria_pai_label' => $this->rotuloCategoriaPai($task->parentTask?->categoria ?? ''),
        'task_code_pai' => $task->parentTask?->taskCode ?? '',
        'prioridade' => $task->prioridade ?: 'Média',
        'parent_task_id' => $task->parent_task_id ? (int) $task->parent_task_id : null,
        'descricao' => $task->descricao,
        'protocolo' => $task->protocolo,
      ];
    });

    return $this->aplicarFiltrosPosQuery($normalizadas, $filtros);
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
      $query->where('op_tasks.prioridade', $filtros['prioridade']);
    }

    // Período aplicado em aplicarFiltrosPosQuery (usa data_criacao / data_conclusao unificadas)

    if (! empty($filtros['busca'])) {
      $busca = $filtros['busca'];
      $query->where(function (Builder $q) use ($busca) {
        $q->where('op_tasks.taskCode', 'like', "%{$busca}%")
          ->orWhere('op_tasks.numero_os', 'like', "%{$busca}%")
          ->orWhere('op_tasks.ordem_servico', 'like', "%{$busca}%")
          ->orWhere('op_tasks.titulo', 'like', "%{$busca}%")
          ->orWhere('op_tasks.responsavel', 'like', "%{$busca}%");
      });
    }

    if (! empty($filtros['categoriaPai'])) {
      $categoria = $filtros['categoriaPai'];
      $query->whereHas('parentTask', fn (Builder $q) => $q->where('categoria', $categoria));
    }
  }

  private function aplicarFiltrosPosQuery(Collection $items, array $filtros): Collection
  {
    if (! empty($filtros['tecnico'])) {
      $tecnico = mb_strtolower($filtros['tecnico']);
      $items = $items->filter(fn (array $item) => str_contains(mb_strtolower($item['tecnico']), $tecnico));
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

  private function resolverNomeTecnico(OpTask $task, ?OsTecnico $registro): string
  {
    $nome = trim((string) $task->responsavel);
    if ($nome !== '') {
      return $nome;
    }

    $nomeOs = trim((string) ($registro->tecnico_nome ?? ''));

    return $nomeOs !== '' ? $nomeOs : 'Sem técnico';
  }

  private function rotuloCategoriaPai(?string $categoria): string
  {
    return match ($categoria) {
      'rompimentos', 'rompimento' => 'Rompimentos',
      'troca-poste' => 'Troca de poste',
      'otimizacao-rede' => 'Otimização de rede',
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
