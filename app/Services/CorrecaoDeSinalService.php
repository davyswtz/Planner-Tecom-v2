<?php

namespace App\Services;

use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class CorrecaoDeSinalService
{
    public function __construct(
        private OpTaskService $opTaskService,
        private GoogleChatService $googleChatService,
    ) {}

    public function getCorrecoesDeSinal(
        int $limit = 10,
        int $offset = 0,
        ?string $status = null,
        ?string $regiao = null,
        ?string $tecnico = null,
        ?string $taskCode = null,
        ?string $dataInicio = null,
        ?string $dataFim = null,
    ) {
        $query = OpTask::tarefasPai(OpTask::CATEGORIAS_CORRECAO_SINAL)
            ->orderBy("updated_at", "desc")
            ->when(
                $status,
                fn($q) => $q->whereIn(
                    "status",
                    $this->statusParaConsulta($status),
                ),
            )
            ->when($regiao, fn($q) => $q->where("regiao", $regiao))
            ->when(
                $tecnico,
                fn($q) => $q->where("responsavel", "like", "%{$tecnico}%"),
            )
            ->when($taskCode, fn($q) => $q->buscaTexto($taskCode))
            ->when(
                $dataInicio,
                fn($q) => $q->whereDate("criadaEm", ">=", $dataInicio),
            )
            ->when(
                $dataFim,
                fn($q) => $q->whereDate("criadaEm", "<=", $dataFim),
            );

        if ($status === "Finalizada") {
            return $query
                ->limit(1000)
                ->offset($offset)
                ->get()
                ->map(fn(OpTask $item) => $this->normalizarParaExibicao($item));
        }

        return $query
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn(OpTask $item) => $this->normalizarParaExibicao($item));
    }

    /**
     * Mapeia o status do kanban para valores legados gravados no banco.
     */
    private function statusParaConsulta(string $status): array
    {
        return match ($status) {
            "Criada" => ["Criada", "Backlog"],
            "Finalizada" => ["Finalizada", "Concluída"],
            default => [$status],
        };
    }

    public function normalizarParaExibicao(OpTask $correcao): array
    {
        $dados = $correcao->toArray();

        $nome = trim((string) ($dados["nome_cliente"] ?? ""));
        $titulo = trim((string) ($dados["titulo"] ?? ""));
        if ($nome === "") {
            $nome = $titulo;
        }

        $setor = trim((string) ($dados["setor"] ?? ""));
        if ($setor === "") {
            $setor = trim((string) ($dados["localizacao_texto"] ?? ""));
        }

        $codigoExibicao = trim((string) ($dados["taskCode"] ?? ""));
        if ($codigoExibicao === "" && str_contains($titulo, "·")) {
            $codigoExibicao = trim(explode("·", $titulo, 2)[0]);
        }

        $status = trim((string) ($dados["status"] ?? ""));

        return array_merge($dados, [
            "nome" => $nome,
            "setor" => $setor,
            "localizacao_texto" =>
                trim((string) ($dados["localizacao_texto"] ?? "")) ?: $setor,
            "codigo_exibicao" => $codigoExibicao,
            "status_exibicao" => $this->statusParaExibicao($status),
            "status_kanban" => $this->statusParaKanban($status),
            "sinal_rx" => $this->extrairSinalDaDescricao(
                (string) ($dados["descricao"] ?? ""),
                ["Sinal Chegada", "Sinal RX"],
            ),
            "sinal_rx_olt" => $this->extrairSinalDaDescricao(
                (string) ($dados["descricao"] ?? ""),
                ["Sinal Retorno", "Sinal RX OLT", "Sinal de Retorno"],
            ),
        ]);
    }

    private function statusParaExibicao(string $status): string
    {
        return match ($status) {
            "Backlog" => "Criada",
            "Concluída" => "Finalizada",
            default => $status,
        };
    }

    private function statusParaKanban(string $status): string
    {
        return match ($status) {
            "Backlog" => "Criada",
            "Concluída" => "Finalizada",
            default => $status,
        };
    }

    public function createCorrecaoDeSinal(array $dados): OpTask
    {
        $dados = OpTask::filtrarEntradaCliente($dados);
        $dados["categoria"] = "correcao-atenuacao";
        $dados["taskCode"] = $this->opTaskService->gerarTaskCode($dados);

        return OpTask::create($dados);
    }

    /**
     * Cria tarefa a partir de um cliente com sinal ruim na busca de caixa.
     */
    public function createFromClienteCaixa(array $dados): OpTask
    {
        $nome = trim((string) ($dados["nome_cliente"] ?? "Cliente"));
        $caixa = trim((string) ($dados["caixa"] ?? ""));
        $porta = $dados["porta"] ?? "—";
        $serial = trim((string) ($dados["serial"] ?? "—"));
        $rxChegada = $this->normalizarSinalDbm($dados["sinal_rx"] ?? null);
        $rxRetorno = $this->normalizarSinalDbm($dados["sinal_rx_olt"] ?? null);
        $rxChegadaTexto = $this->formatarSinalDbm($dados["sinal_rx"] ?? null);
        $rxRetornoTexto = $this->formatarSinalDbm(
            $dados["sinal_rx_olt"] ?? null,
        );
        $codigo = (string) ($dados["codigo_cliente"] ?? "");
        $idServico = (string) ($dados["id_cliente_servico"] ?? "");

        $descricao = implode(
            "\n",
            array_filter([
                "Cliente: {$nome}" . ($codigo !== "" ? " (#{$codigo})" : ""),
                $caixa !== "" ? "Caixa: {$caixa}" : null,
                "Porta: {$porta}",
                "Serial: {$serial}",
                $rxChegadaTexto !== null
                    ? "Sinal Chegada: {$rxChegadaTexto}"
                    : null,
                $rxRetornoTexto !== null
                    ? "Sinal Retorno: {$rxRetornoTexto}"
                    : null,
                $idServico !== "" ? "ID serviço Nicon: {$idServico}" : null,
            ]),
        );

        $prioridade = "Média";
        if (
            ($rxChegada !== null && $rxChegada < -27.0) ||
            ($rxRetorno !== null && $rxRetorno < -28.0)
        ) {
            $prioridade = "Alta";
        }

        return $this->createCorrecaoDeSinal([
            "titulo" => "Correção sinal — {$nome}",
            "nome_cliente" => $nome,
            "setor" => $caixa,
            "regiao" => trim(
                (string) ($dados["regiao"] ?? "Governador Valadares"),
            ),
            "status" => "Criada",
            "prioridade" => $prioridade,
            "descricao" => $descricao,
            "localizacao_texto" => $caixa,
            "coordenadas" => "",
        ]);
    }

    /**
     * @param  array<int, string>  $rotulos
     */
    private function extrairSinalDaDescricao(
        string $descricao,
        array $rotulos,
    ): ?float {
        $texto = trim($descricao);
        if ($texto === "") {
            return null;
        }

        foreach ($rotulos as $rotulo) {
            $padrao =
                "/^" .
                preg_quote($rotulo, "/") .
                "\s*:\s*(-?\d+(?:[\.,]\d+)?)/mi";
            if (preg_match($padrao, $texto, $matches) === 1) {
                return $this->normalizarSinalDbm($matches[1]);
            }
        }

        return null;
    }

    private function normalizarSinalDbm(mixed $valor): ?float
    {
        if ($valor === null || $valor === "") {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $texto = trim(str_replace(",", ".", (string) $valor));
        if (
            $texto === "" ||
            !preg_match("/-?\d+(?:\.\d+)?/", $texto, $matches)
        ) {
            return null;
        }

        return (float) $matches[0];
    }

    private function formatarSinalDbm(mixed $valor): ?string
    {
        $numero = $this->normalizarSinalDbm($valor);

        return $numero === null ? null : number_format($numero, 2) . " dBm";
    }

    public function buscarEndereco(string $coordenada): string
    {
        $partes = explode(",", $coordenada);
        $lat = $partes[0] ?? "";
        $lng = $partes[1] ?? "";

        $response = Http::withHeaders([
            "User-Agent" => "Planner/1.0",
        ])->get("https://nominatim.openstreetmap.org/reverse", [
            "lat" => $lat,
            "lon" => $lng,
            "format" => "json",
        ]);

        return $response->json("display_name") ?? "Endereço não encontrado";
    }

    public function updateCorrecaoDeSinal(
        OpTask $correcao,
        array $dados,
    ): OpTask {
        $statusAnterior = $correcao->status;
        $dados = OpTask::filtrarEntradaCliente($dados);

        if (isset($dados["status"]) && $dados["status"] === "Finalizada") {
            $osPendentes = OpTask::where("parent_task_id", $correcao->id)
                ->where("status", "!=", "Finalizada")
                ->count();

            if ($osPendentes > 0) {
                abort(
                    422,
                    "Finalize todas as OS antes de finalizar a correção de sinal",
                );
            }
        }

        $correcao->update($dados);

        if (isset($dados["status"]) && $dados["status"] !== $statusAnterior) {
            $tarefaAtualizada = $correcao->fresh();
            $mensagem = $this->googleChatService->montarMensagemStatus(
                $tarefaAtualizada->toArray(),
                $statusAnterior,
                $dados["status"],
            );
            $googleChatService = $this->googleChatService;
            app()->terminating(function () use (
                $tarefaAtualizada,
                $mensagem,
                $googleChatService,
                $dados,
            ) {
                $googleChatService->enviarNotificacao(
                    $tarefaAtualizada,
                    $mensagem,
                    $dados["status"],
                );
            });
        }

        return $correcao->fresh();
    }

    public function deleteCorrecaoDeSinal(OpTask $correcao): void
    {
        OpTask::where("parent_task_id", $correcao->id)
            ->where("categoria", "ordem-servico")
            ->delete();
        $correcao->delete();
    }
}
