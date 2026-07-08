<?php

namespace App\Console\Commands;

use App\Services\Nicon\NiconApiService;
use App\Services\Nicon\NiconWebService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Comando TEMPORÁRIO para investigar o payload de sinal do Nicon.
 * Usar para descobrir o nome do campo RX OLT (Retorno).
 * Remover após a investigação.
 */
class NiconInspecionarSinal extends Command
{
    protected $signature = 'nicon:inspecionar-sinal
        {id_cliente_servico : ID do cliente/serviço no Nicon}
        {--serial= : Serial da ONU (opcional)}';

    protected $description = "[TEMP] Mostra o payload COMPLETO do Nicon para um cliente — usada para descobrir campos de sinal disponíveis";

    public function handle(
        NiconWebService $niconWeb,
        NiconApiService $niconApi,
    ): int {
        $id = (int) $this->argument("id_cliente_servico");
        $serial = $this->option("serial");

        $this->info("═══════════════════════════════════════════════");
        $this->info("  Investigando sinal do Nicon — cliente #{$id}");
        $this->info("═══════════════════════════════════════════════");
        $this->newLine();

        // ─── 1. buscar-sinal-atual-cliente (Interface Web) ───
        $this->info("▶ Fonte 1: buscar-sinal-atual-cliente (Web)");
        $this->line(
            "  Endpoint: POST /cliente/atendimento/buscar-sinal-atual-cliente",
        );
        $this->newLine();

        try {
            $sinalWeb = $niconWeb->buscarSinalAtualCliente(
                $id,
                $serial,
                false,
                false,
            );

            if (empty($sinalWeb)) {
                $this->warn(
                    "  ⚠ Resposta vazia. Tentando com forcar_refresh_tr069=true...",
                );
                $sinalWeb = $niconWeb->buscarSinalAtualCliente(
                    $id,
                    $serial,
                    true,
                    false,
                );
            }

            if (empty($sinalWeb)) {
                $this->error("  ✗ Nenhum dado retornado.");
            } else {
                $this->info("  ✓ Resposta recebida! Campos encontrados:");
                $this->newLine();
                $this->exibirPayloadCompleto($sinalWeb, "    ");
            }
        } catch (RuntimeException $e) {
            $this->error("  ✗ Erro: {$e->getMessage()}");
        }

        $this->newLine();
        $this->line("───────────────────────────────────────────────");
        $this->newLine();

        // ─── 2. buscar-sinal-onu (API App-Técnico) ───
        $this->info("▶ Fonte 2: buscar-sinal-onu (API App-Técnico)");
        $this->line(
            "  Endpoint: GET /api/app-tecnico/cliente-servico/buscar-sinal-onu/{$id}",
        );
        $this->newLine();

        try {
            $sinalApi = $niconApi->buscarSinalOnu($id, false);

            if (empty($sinalApi)) {
                $this->error("  ✗ Nenhum dado retornado.");
            } else {
                $this->info("  ✓ Resposta recebida! Campos encontrados:");
                $this->newLine();
                $this->exibirPayloadCompleto($sinalApi, "    ");
            }
        } catch (RuntimeException $e) {
            $this->error("  ✗ Erro: {$e->getMessage()}");
        }

        $this->newLine();
        $this->line("───────────────────────────────────────────────");
        $this->newLine();

        // ─── Resumo: destacar campos candidatos a RX OLT ───
        $this->info("▶ Campos candidatos a RX OLT (Retorno):");
        $this->newLine();

        $candidatos = [
            "tx",
            "tx_olt",
            "rx_olt",
            "potencia_olt",
            "olt_rx",
            "olt_potencia",
            "power_olt",
            "sinal_olt",
            "olt_rx_power",
            "rx_power_olt",
            "potencia_retorno",
            "sinal_retorno",
            "tx_power",
            "optical_tx",
            "onu_tx",
            "ont_tx",
        ];

        $encontrados = [];
        foreach ([$sinalWeb ?? [], $sinalApi ?? []] as $payload) {
            $this->buscarCamposRecursivo($payload, $candidatos, $encontrados);
        }

        if ($encontrados) {
            foreach ($encontrados as $caminho => $valor) {
                $this->line(
                    "  🔍 <fg=green>{$caminho}</> = <fg=yellow>{$valor}</>",
                );
            }
        } else {
            $this->warn("  Nenhum campo candidato encontrado automaticamente.");
            $this->warn(
                "  Analise o payload completo acima e procure por campos",
            );
            $this->warn(
                "  que contenham valores em dBm negativos (ex: -24.50).",
            );
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════");
        $this->info("  Após identificar o campo, remova este comando.");
        $this->info("═══════════════════════════════════════════════");

        return self::SUCCESS;
    }

    private function exibirPayloadCompleto(
        mixed $data,
        string $indent = "",
        string $caminho = "",
    ): void {
        if (!is_array($data)) {
            $this->line(
                "{$indent}<fg=gray>{$caminho}</> = <fg=yellow>" .
                    var_export($data, true) .
                    "</>",
            );
            return;
        }

        foreach ($data as $chave => $valor) {
            $caminhoAtual =
                $caminho !== "" ? "{$caminho}.{$chave}" : (string) $chave;

            if (is_array($valor)) {
                $this->line(
                    "{$indent}<fg=cyan>{$chave}</> <fg=gray>→ [array]</>",
                );
                $this->exibirPayloadCompleto(
                    $valor,
                    $indent . "  ",
                    $caminhoAtual,
                );
            } else {
                $valorFormatado = match (true) {
                    is_null($valor) => "<fg=gray>null</>",
                    is_bool($valor) => $valor
                        ? "<fg=green>true</>"
                        : "<fg=red>false</>",
                    is_numeric($valor) => "<fg=yellow>{$valor}</>",
                    default => "<fg=white>" .
                        mb_substr((string) $valor, 0, 120) .
                        "</>",
                };

                $this->line(
                    "{$indent}<fg=cyan>{$chave}</> = {$valorFormatado}",
                );
            }
        }
    }

    /**
     * @param array<string, string> $encontrados
     */
    private function buscarCamposRecursivo(
        mixed $data,
        array $candidatos,
        array &$encontrados,
        string $caminho = "",
    ): void {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $chave => $valor) {
            $caminhoAtual =
                $caminho !== "" ? "{$caminho}.{$chave}" : (string) $chave;
            $chaveNorm = strtolower((string) $chave);

            if (
                in_array($chaveNorm, $candidatos, true) &&
                $valor !== null &&
                $valor !== ""
            ) {
                $encontrados[$caminhoAtual] = (string) $valor;
            }

            if (is_array($valor)) {
                $this->buscarCamposRecursivo(
                    $valor,
                    $candidatos,
                    $encontrados,
                    $caminhoAtual,
                );
            }
        }
    }
}
