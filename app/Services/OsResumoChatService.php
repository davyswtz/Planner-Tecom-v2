<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Collection;

class OsResumoChatService
{
    /** @return array{os_total: int, os_finalizadas: int, por_tecnico: array<string, int>} */
    public function calcular(int $parentTaskId): array
    {
        $osList = $this->listarOsVinculadas($parentTaskId);
        $finalizadas = $osList->filter(fn (OpTask $os) => $this->osEstaFinalizada($os->status));

        $porTecnico = [];
        foreach ($finalizadas as $os) {
            $tecnicos = OpTask::parseResponsaveis($os->responsavel);
            if ($tecnicos === []) {
                $tecnicos = ['Sem técnico'];
            }

            foreach ($tecnicos as $tecnico) {
                $nome = trim((string) $tecnico) !== '' ? trim((string) $tecnico) : 'Sem técnico';
                $porTecnico[$nome] = ($porTecnico[$nome] ?? 0) + 1;
            }
        }

        arsort($porTecnico);

        return [
            'os_total' => $osList->count(),
            'os_finalizadas' => $finalizadas->count(),
            'por_tecnico' => $porTecnico,
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

        // Tarefa pai: {os_sequencia} lista as OS (comportamento esperado no template da pai).
        // OS filha: {os_sequencia} é só a posição (1, 2, 3…).
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
        $porTecnico = $dados['por_tecnico'] ?? [];

        if ($total === 0) {
            return implode("\n", [
                '📊 *Resumo de OS*',
                '━━━━━━━━━━━━━━━━━━━━',
                '📋 Nenhuma OS vinculada a esta tarefa.',
            ]);
        }

        $linhas = [
            '📊 *Resumo de OS*',
            '━━━━━━━━━━━━━━━━━━━━',
            "📋 *Total de OS:* {$total}",
            "✅ *Finalizadas:* {$finalizadas}",
        ];

        if ($porTecnico !== []) {
            $linhas[] = '';
            $linhas[] = '👷 *Por técnico:*';
            foreach ($porTecnico as $nome => $quantidade) {
                $rotulo = $quantidade === 1 ? '1 OS' : "{$quantidade} OS";
                $linhas[] = "• *{$nome}* — {$rotulo}";
            }
        } elseif ($finalizadas === 0) {
            $linhas[] = '';
            $linhas[] = '👷 Nenhuma OS finalizada registrada.';
        }

        return implode("\n", $linhas);
    }

    /** @param array<string, int> $porTecnico */
    public function formatarTecnicos(array $porTecnico): string
    {
        if ($porTecnico === []) {
            return '—';
        }

        $linhas = [];
        foreach ($porTecnico as $nome => $quantidade) {
            $rotulo = $quantidade === 1 ? '1 OS' : "{$quantidade} OS";
            $linhas[] = "• {$nome} — {$rotulo}";
        }

        return implode("\n", $linhas);
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
