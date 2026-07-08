<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Collection;

class OsResumoChatService
{
    /**
     * @return array{
     *     os_total: int,
     *     os_finalizadas: int,
     *     por_tecnico: array<string, array{quantidade: int, atividades: array<int, array{sequencia: int, titulo: string, colaborativa: bool}>}>
     * }
     */
    public function calcular(int $parentTaskId): array
    {
        $osList = $this->listarOsVinculadas($parentTaskId);
        $finalizadas = $osList->filter(fn (OpTask $os) => $this->osEstaFinalizada($os->status));

        $porTecnico = [];

        foreach ($osList->values() as $indice => $os) {
            if (! $this->osEstaFinalizada($os->status)) {
                continue;
            }

            $sequencia = (int) ($os->sequencia ?? 0);
            if ($sequencia <= 0) {
                $sequencia = $indice + 1;
            }

            $titulo = $this->tituloOsLimpo($os->titulo);
            if ($titulo === '') {
                $titulo = trim((string) ($os->taskCode ?? '')) ?: "OS #{$os->id}";
            }

            $tecnicos = OpTask::parseResponsaveis($os->responsavel);
            if ($tecnicos === []) {
                $tecnicos = ['Sem técnico'];
            }

            $atividade = [
                'sequencia' => $sequencia,
                'titulo' => $titulo,
                'colaborativa' => count($tecnicos) > 1,
            ];

            foreach ($tecnicos as $tecnico) {
                $chave = trim((string) $tecnico) !== '' ? trim((string) $tecnico) : 'Sem técnico';

                if (! isset($porTecnico[$chave])) {
                    $porTecnico[$chave] = [
                        'quantidade' => 0,
                        'atividades' => [],
                    ];
                }

                $porTecnico[$chave]['atividades'][] = $atividade;
                $porTecnico[$chave]['quantidade']++;
            }
        }

        foreach ($porTecnico as &$dadosTecnico) {
            usort(
                $dadosTecnico['atividades'],
                fn (array $a, array $b) => $a['sequencia'] <=> $b['sequencia']
            );
        }
        unset($dadosTecnico);

        $chaves = array_keys($porTecnico);
        usort($chaves, function (string $a, string $b) use ($porTecnico): int {
            $quantidadeA = $porTecnico[$a]['quantidade'];
            $quantidadeB = $porTecnico[$b]['quantidade'];

            if ($quantidadeA !== $quantidadeB) {
                return $quantidadeB <=> $quantidadeA;
            }

            return strcasecmp($this->nomeExibicao($a), $this->nomeExibicao($b));
        });

        $porTecnicoOrdenado = [];
        foreach ($chaves as $chave) {
            $porTecnicoOrdenado[$chave] = $porTecnico[$chave];
        }

        return [
            'os_total' => $osList->count(),
            'os_finalizadas' => $finalizadas->count(),
            'por_tecnico' => $porTecnicoOrdenado,
        ];
    }

    /** @return array<string, string> */
    public function paraPayload(int $parentTaskId): array
    {
        $dados = $this->calcular($parentTaskId);

        return array_merge([
            'os_total' => (string) $dados['os_total'],
            'os_finalizadas' => (string) $dados['os_finalizadas'],
            'os_resumo_tecnicos' => $this->formatarTecnicos($dados['por_tecnico']),
            'os_resumo' => $this->formatarBloco($dados),
        ], $this->sequenciaParaPayload($parentTaskId));
    }

    /**
     * Dados de sequência das OS vinculadas à tarefa pai.
     *
     * - os_sequencia: na mensagem da OS, a posição (1, 2, 3…);
     *   na mensagem da tarefa pai, a lista numerada (mesmo conteúdo de os_lista)
     * - os_lista: nomes de todas as OS numerados na ordem definida
     *
     * @return array{os_sequencia: string, os_lista: string}
     */
    public function sequenciaParaPayload(int $parentTaskId, ?int $osId = null): array
    {
        $lista = $this->listarOsVinculadas($parentTaskId);

        if ($lista->isEmpty()) {
            return [
                'os_sequencia' => '—',
                'os_lista' => '—',
            ];
        }

        $linhas = [];
        $sequenciaAtual = null;

        foreach ($lista->values() as $indice => $os) {
            $posicao = $indice + 1;
            $nome = $this->tituloOsLimpo($os->titulo);
            if ($nome === '') {
                $nome = trim((string) ($os->taskCode ?? '')) ?: "OS #{$os->id}";
            }

            $linhas[] = "{$posicao}. {$nome}";

            if ($osId !== null && (int) $os->id === $osId) {
                $sequenciaAtual = (string) $posicao;
            }
        }

        $listaTexto = implode("\n", $linhas);

        return [
            'os_sequencia' => $sequenciaAtual ?? $listaTexto,
            'os_lista' => $listaTexto,
        ];
    }

    public function tituloOsLimpo(?string $titulo): string
    {
        return trim((string) preg_replace('/^OS\s*[—\-–]\s*/u', '', (string) $titulo));
    }

    public function formatarBloco(array $dados): string
    {
        $total = (int) ($dados['os_total'] ?? 0);
        $finalizadas = (int) ($dados['os_finalizadas'] ?? 0);
        $porTecnico = $this->normalizarPorTecnico($dados['por_tecnico'] ?? []);

        if ($total === 0) {
            return implode("\n", [
                '📊 *RESUMO DE OS*',
                '━━━━━━━━━━━━━━━━━━━━',
                '📋 Nenhuma OS vinculada a esta tarefa.',
            ]);
        }

        $linhas = [
            '📊 *RESUMO DE OS*',
            '━━━━━━━━━━━━━━━━━━━━',
            "📋 *Total da Tarefa:* {$total}",
            "✅ *Finalizadas (NicOn):* {$finalizadas}",
        ];

        if ($porTecnico !== []) {
            $linhas[] = '';
            $linhas[] = '👷 *PRODUTIVIDADE INDIVIDUAL*';
            $linhas = array_merge($linhas, $this->formatarLinhasTecnicos($porTecnico));
        } elseif ($finalizadas === 0) {
            $linhas[] = '';
            $linhas[] = '👷 Nenhuma OS finalizada registrada.';
        }

        return implode("\n", $linhas);
    }

    /**
     * @param array<string, array{quantidade: int, atividades: array<int, array{sequencia: int, titulo: string, colaborativa: bool}>}> $porTecnico
     */
    public function formatarTecnicos(array $porTecnico): string
    {
        $porTecnico = $this->normalizarPorTecnico($porTecnico);

        if ($porTecnico === []) {
            return '—';
        }

        return implode("\n", $this->formatarLinhasTecnicos($porTecnico));
    }

    public function statusDisparaResumo(string $status): bool
    {
        $chave = strtolower(str_replace('_', ' ', trim($status)));

        return in_array($chave, [
            'finalizada',
            'finalizar',
            'concluída',
            'concluida',
            'concluído',
            'concluido',
        ], true);
    }

    /** @return Collection<int, OpTask> */
    public function listarOsVinculadas(int $parentTaskId): Collection
    {
        return OpTask::query()
            ->where('parent_task_id', $parentTaskId)
            ->where('categoria', 'ordem-servico')
            ->orderBy('sequencia')
            ->orderBy('criadaEm')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param array<string, array{quantidade: int, atividades: array<int, array{sequencia: int, titulo: string, colaborativa: bool}>}> $porTecnico
     * @return list<string>
     */
    private function formatarLinhasTecnicos(array $porTecnico): array
    {
        $linhas = [];
        $indiceTecnico = 0;
        $totalTecnicos = count($porTecnico);

        foreach ($porTecnico as $chave => $info) {
            $nome = app(TecnicoChatMencaoService::class)->mencionar($chave);
            $quantidade = (int) ($info['quantidade'] ?? 0);
            $rotulo = $quantidade === 1 ? '1 OS' : "{$quantidade} OS";

            $linhas[] = "• {$nome} — {$rotulo}";

            foreach ($info['atividades'] ?? [] as $atividade) {
                $sequencia = (int) ($atividade['sequencia'] ?? 0);
                $titulo = trim((string) ($atividade['titulo'] ?? '')) ?: '—';
                $sufixo = ! empty($atividade['colaborativa']) ? ' 🤝' : '';

                $linhas[] = "↳ {$sequencia}° Atividade - {$titulo}{$sufixo}";
            }

            $indiceTecnico++;
            if ($indiceTecnico < $totalTecnicos) {
                $linhas[] = '';
            }
        }

        return $linhas;
    }

    /**
     * Aceita o formato legado (contagem inteira) e o formato detalhado.
     *
     * @param array<string, mixed> $porTecnico
     * @return array<string, array{quantidade: int, atividades: array<int, array{sequencia: int, titulo: string, colaborativa: bool}>}>
     */
    private function normalizarPorTecnico(array $porTecnico): array
    {
        $normalizado = [];

        foreach ($porTecnico as $chave => $valor) {
            if (is_array($valor)) {
                $normalizado[$chave] = [
                    'quantidade' => (int) ($valor['quantidade'] ?? 0),
                    'atividades' => is_array($valor['atividades'] ?? null) ? $valor['atividades'] : [],
                ];

                continue;
            }

            $normalizado[$chave] = [
                'quantidade' => (int) $valor,
                'atividades' => [],
            ];
        }

        return $normalizado;
    }

    private function nomeExibicao(string $username): string
    {
        try {
            return app(TecnicoNomeResolver::class)->resolverOuOriginal($username)['tecnico'];
        } catch (\Throwable) {
            $nome = trim($username);

            return $nome !== '' ? $nome : 'Sem técnico';
        }
    }

    private function osEstaFinalizada(?string $status): bool
    {
        $chave = strtolower(str_replace('_', ' ', trim((string) $status)));

        return in_array($chave, [
            'finalizada',
            'finalizar',
            'concluída',
            'concluida',
            'fechada',
        ], true);
    }
}
