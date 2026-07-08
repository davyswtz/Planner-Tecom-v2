<?php

namespace App\Services;

use App\Models\OpTask;
use Carbon\Carbon;

class OpTaskHistoricoService
{
  /** @var array<string, string> */
  private const CAMPOS_RASTREADOS = [
    'status' => 'status',
    'prioridade' => 'prioridade',
    'responsavel' => 'responsável',
    'titulo' => 'título',
    'regiao' => 'região',
    'descricao' => 'descrição',
    'setor' => 'setor/CTO',
    'localizacao_texto' => 'localização',
    'coordenadas' => 'coordenadas',
    'numero_os' => 'número da OS',
    'nome_cliente' => 'cliente',
    'protocolo' => 'protocolo',
    'prazo' => 'prazo',
    'clientesAfetados' => 'clientes afetados',
  ];

  public function registrarCriacao(OpTask $task, ?string $usuario = null): void
  {
    $status = trim((string) ($task->status ?? 'Criada')) ?: 'Criada';

    $this->adicionarEvento($task, [
      'tipo' => 'criacao',
      'descricao' => "Criou a tarefa com status {$status}",
      'campo' => null,
      'de' => null,
      'para' => $status,
    ], $usuario);
  }

  public function registrarAlteracoes(OpTask $task, ?string $usuario = null): void
  {
    $alteracoes = [];

    foreach (self::CAMPOS_RASTREADOS as $campo => $rotulo) {
      if (! $task->wasChanged($campo)) {
        continue;
      }

      $de = $this->formatarValor($campo, $task->getOriginal($campo));
      $para = $this->formatarValor($campo, $task->getAttribute($campo));

      if ($de === $para) {
        continue;
      }

      $alteracoes[] = [
        'campo' => $campo,
        'rotulo' => $rotulo,
        'de' => $de,
        'para' => $para,
      ];
    }

    if ($alteracoes === []) {
      return;
    }

    foreach ($alteracoes as $item) {
      $tipo = $item['campo'] === 'status' ? 'status' : 'alteracao';
      $descricao = $item['campo'] === 'status'
        ? "Moveu de {$item['de']} para {$item['para']}"
        : "Alterou {$item['rotulo']} de \"{$item['de']}\" para \"{$item['para']}\"";

      $this->adicionarEvento($task, [
        'tipo' => $tipo,
        'descricao' => $descricao,
        'campo' => $item['campo'],
        'de' => $item['de'],
        'para' => $item['para'],
      ], $usuario);
    }
  }

  /** @return array{eventos: array<int, array<string, mixed>>, ultima: ?array<string, mixed>} */
  public function listar(OpTask $task): array
  {
    $eventos = $this->parseEventos($task->historico);

    if ($eventos === [] && filled($task->criadaEm)) {
      $eventos[] = [
        'data' => $this->normalizarDataLegado($task->criadaEm),
        'usuario' => '—',
        'tipo' => 'legado',
        'descricao' => 'Tarefa criada antes do histórico detalhado',
        'campo' => null,
        'de' => null,
        'para' => null,
      ];
    }

    usort($eventos, function (array $a, array $b): int {
      $dataA = $this->parseDataEvento($a['data'] ?? null)?->getTimestamp() ?? 0;
      $dataB = $this->parseDataEvento($b['data'] ?? null)?->getTimestamp() ?? 0;

      return $dataB <=> $dataA;
    });

    $ultima = $eventos[0] ?? null;

    return [
      'eventos' => $eventos,
      'ultima' => $ultima,
    ];
  }

  public function resumoParaTemplate(?string $raw): string
  {
    $eventos = $this->parseEventos($raw);
    if ($eventos === []) {
      return '—';
    }

    $statusEventos = array_values(array_filter(
      $eventos,
      static fn (array $evento) => ($evento['tipo'] ?? '') === 'status'
        || (($evento['campo'] ?? '') === 'status' && ! empty($evento['de']) && ! empty($evento['para']))
    ));

    if ($statusEventos !== []) {
      $partes = [];
      foreach (array_reverse($statusEventos) as $evento) {
        if (! empty($evento['de'])) {
          $partes[] = (string) $evento['de'];
        }
        if (! empty($evento['para'])) {
          $partes[] = (string) $evento['para'];
        }
      }

      $cadeia = array_values(array_unique(array_filter($partes, static fn ($valor) => $valor !== '' && $valor !== '—')));
      if ($cadeia !== []) {
        return implode(' → ', $cadeia);
      }
    }

    return trim((string) ($eventos[0]['descricao'] ?? '')) ?: '—';
  }

  /** @param array<string, mixed>|OpTask $task */
  public function calcularDuracaoAtivaMinutos(array|OpTask $task): int
  {
    $dados = $task instanceof OpTask ? $task->toArray() : $task;
    $eventos = $this->eventosCronologicos($dados);

    if ($eventos === []) {
      return max(0, (int) ($dados['active_duration_minutes'] ?? 0));
    }

    $totalSegundos = 0;
    $emAndamentoDesde = null;

    foreach ($eventos as $evento) {
      $data = $this->parseDataEvento($evento['data'] ?? null);
      if ($data === null) {
        continue;
      }

      $tipo = (string) ($evento['tipo'] ?? '');

      if ($tipo === 'criacao') {
        if ($this->isEmAndamento($evento['para'] ?? '')) {
          $emAndamentoDesde = $data;
        }

        continue;
      }

      if ($tipo !== 'status' && (($evento['campo'] ?? '') !== 'status')) {
        continue;
      }

      $de = (string) ($evento['de'] ?? '');
      $para = (string) ($evento['para'] ?? '');

      if ($this->isEmAndamento($de) && ! $this->isEmAndamento($para) && $emAndamentoDesde !== null) {
        $totalSegundos += max(0, $emAndamentoDesde->diffInSeconds($data));
        $emAndamentoDesde = null;
      }

      if (! $this->isEmAndamento($de) && $this->isEmAndamento($para)) {
        $emAndamentoDesde = $data;
      }
    }

    if ($emAndamentoDesde !== null) {
      $totalSegundos += max(0, $emAndamentoDesde->diffInSeconds(now()));
    }

    return max(0, (int) round($totalSegundos / 60));
  }

  /** @param array<string, mixed>|OpTask $task */
  public function operadorInicioAtividade(array|OpTask $task): ?string
  {
    $dados = $task instanceof OpTask ? $task->toArray() : $task;

    foreach ($this->eventosCronologicos($dados) as $evento) {
      if (($evento['tipo'] ?? '') === 'criacao' && $this->isEmAndamento($evento['para'] ?? '')) {
        $usuario = $this->normalizarUsuarioOperador($evento['usuario'] ?? null);
        if ($usuario !== null) {
          return $usuario;
        }
      }

      $isStatusEvent = ($evento['tipo'] ?? '') === 'status'
        || (($evento['campo'] ?? '') === 'status');

      if (! $isStatusEvent || ! $this->isEmAndamento($evento['para'] ?? '')) {
        continue;
      }

      $usuario = $this->normalizarUsuarioOperador($evento['usuario'] ?? null);
      if ($usuario !== null) {
        return $usuario;
      }
    }

    return null;
  }

  /** @param array<string, mixed>|OpTask $task */
  public function operadorInicioExibicao(array|OpTask $task, ?string $fallback = null): string
  {
    $usuario = $this->operadorInicioAtividade($task);
    if ($usuario === null) {
      $usuario = trim((string) $fallback);
    }

    if ($usuario === '') {
      return '—';
    }

    try {
      return app(TecnicoNomeResolver::class)->resolverOuOriginal($usuario)['tecnico'];
    } catch (\Throwable) {
      return $usuario;
    }
  }

  public function formatarDuracaoAtiva(int $minutos): string
  {
    if ($minutos <= 0) {
      return '—';
    }

    $horas = intdiv($minutos, 60);
    $mins = $minutos % 60;

    if ($horas > 0 && $mins > 0) {
      return "{$horas}h {$mins}min";
    }

    if ($horas > 0) {
      return "{$horas}h";
    }

    return "{$mins}min";
  }

  /** @param array<string, mixed> $task */
  private function eventosCronologicos(array $task): array
  {
    $eventos = $this->parseEventos($task['historico'] ?? null);

    if ($eventos === [] && filled($task['criadaEm'] ?? null)) {
      $eventos[] = [
        'data' => $this->normalizarDataLegado($task['criadaEm']),
        'usuario' => 'Sistema',
        'tipo' => 'criacao',
        'descricao' => 'Criação da tarefa',
        'campo' => null,
        'de' => null,
        'para' => trim((string) ($task['status'] ?? 'Criada')) ?: 'Criada',
      ];
    }

    usort($eventos, function (array $a, array $b): int {
      $dataA = $this->parseDataEvento($a['data'] ?? null)?->getTimestamp() ?? PHP_INT_MAX;
      $dataB = $this->parseDataEvento($b['data'] ?? null)?->getTimestamp() ?? PHP_INT_MAX;

      if ($dataA !== $dataB) {
        return $dataA <=> $dataB;
      }

      $pesoA = ($a['tipo'] ?? '') === 'criacao' ? 0 : 1;
      $pesoB = ($b['tipo'] ?? '') === 'criacao' ? 0 : 1;

      return $pesoA <=> $pesoB;
    });

    return $eventos;
  }

  private function isEmAndamento(mixed $status): bool
  {
    return $this->normalizarStatusChave($status) === 'em andamento';
  }

  private function normalizarStatusChave(mixed $status): string
  {
    return strtolower(str_replace('_', ' ', trim((string) $status)));
  }

  private function normalizarUsuarioOperador(?string $usuario): ?string
  {
    $usuario = trim((string) $usuario);
    if ($usuario === '' || $usuario === '—' || strcasecmp($usuario, 'Sistema') === 0) {
      return null;
    }

    return $usuario;
  }

  /** @param array<string, mixed> $dados */
  private function adicionarEvento(OpTask $task, array $dados, ?string $usuario = null): void
  {
    $eventos = $this->parseEventos($task->historico);

    $eventos[] = array_merge([
      'data' => now()->toIso8601String(),
      'usuario' => $this->normalizarUsuario($usuario),
    ], $dados);

    OpTask::withoutEvents(function () use ($task, $eventos): void {
      $task->historico = json_encode(
        ['eventos' => $eventos],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
      );
      $task->saveQuietly();
    });
  }

  /** @return array<int, array<string, mixed>> */
  private function parseEventos(?string $raw): array
  {
    if ($raw === null || trim($raw) === '') {
      return [];
    }

    $trimmed = trim($raw);

    if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
      $decoded = json_decode($trimmed, true);
      if (is_array($decoded)) {
        $eventos = $decoded['eventos'] ?? $decoded;

        return is_array($eventos)
          ? array_values(array_filter($eventos, 'is_array'))
          : [];
      }
    }

    return [[
      'data' => null,
      'usuario' => '—',
      'tipo' => 'legado',
      'descricao' => $trimmed,
      'campo' => null,
      'de' => null,
      'para' => null,
    ]];
  }

  private function formatarValor(string $campo, mixed $valor): string
  {
    if ($valor === null) {
      return '—';
    }

    if ($campo === 'prazo' && $valor !== '') {
      try {
        return Carbon::parse((string) $valor)->format('d/m/Y');
      } catch (\Throwable) {
        return trim((string) $valor) ?: '—';
      }
    }

    $texto = trim((string) $valor);

    return $texto !== '' ? $texto : '—';
  }

  private function normalizarUsuario(?string $usuario): string
  {
    $usuario = trim((string) $usuario);

    return $usuario !== '' ? $usuario : 'Sistema';
  }

  private function parseDataEvento(mixed $valor): ?Carbon
  {
    if ($valor === null || $valor === '') {
      return null;
    }

    try {
      return Carbon::parse((string) $valor);
    } catch (\Throwable) {
      return null;
    }
  }

  private function normalizarDataLegado(mixed $valor): ?string
  {
    return $this->parseDataEvento($valor)?->toIso8601String();
  }
}
