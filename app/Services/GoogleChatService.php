<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OpTaskAnexo;
use App\Services\Nicon\NiconChatNotificacaoService;
use Illuminate\Support\Facades\Http;

class GoogleChatService
{
    public function __construct(
        private WebhookService $webhooks,
        private MensagemTemplateService $mensagens,
        private OpTaskAnexoService $anexos,
        private OsResumoChatService $osResumo,
        private NiconChatNotificacaoService $niconChat,
    ) {
    }

    public function enviarNotificacao(OpTask $rompimento, array $mensagem, ?string $statusNovo = null): void
    {
        if (($rompimento->categoria ?? '') === 'tarefas') {
            return;
        }

        if ($statusNovo !== null && ! $this->webhooks->deveNotificarStatus($statusNovo)) {
            return;
        }

        // Paralelo ao Google Chat — não remove webhooks; falhas do Nicon não bloqueiam.
        $this->niconChat->enviarNotificacao($rompimento, $mensagem, $statusNovo);

        $url = $this->webhooks->resolverUrlPorRegiao($rompimento->regiao);

        if (! $url) {
            return;
        }

        if ($rompimento->chat_thread_key) {
            $mensagem['thread'] = ['name' => $rompimento->chat_thread_key];
            Http::timeout($this->timeoutEnvio($mensagem))->post(
                $url . '&messageReplyOption=REPLY_MESSAGE_FALLBACK_TO_NEW_THREAD',
                $mensagem
            );

            return;
        }

        $response = Http::timeout($this->timeoutEnvio($mensagem))->post($url, $mensagem);

        if ($response->successful()) {
            $threadName = $response->json('thread.name');
            if ($threadName) {
                $rompimento->update(['chat_thread_key' => $threadName]);
            }
        }
    }

    /** @param array<string, mixed> $mensagem */
    public function enriquecerMensagemComAnexosOs(OpTask $os, array $mensagem): array
    {
        if (($os->categoria ?? '') !== 'ordem-servico') {
            return $mensagem;
        }

        try {
            $itens = $this->anexos->listarParaChat($os);
        } catch (\Throwable) {
            return $mensagem;
        }

        if ($itens === []) {
            return $mensagem;
        }

        $limite = max(1, (int) config('planner.anexos_chat.max_imagens_por_mensagem', 10));
        $itens = array_slice($itens, 0, $limite);
        $qtd = count($itens);

        $widgets = array_map(
            fn (array $item) => [
                'image' => $this->montarImagemChatWidget(
                    (string) $item['url'],
                    mb_substr((string) ($item['nome_arquivo'] ?? 'Anexo'), 0, 120),
                ),
            ],
            $itens,
        );

        $mensagem['cardsV2'] = [[
            'cardId' => 'os-anexos-' . $os->id . '-' . now()->timestamp,
            'card' => [
                'header' => [
                    'title' => '📎 Anexos da OS',
                    'subtitle' => $qtd === 1 ? '1 imagem' : "{$qtd} imagens",
                ],
                'sections' => [[
                    'widgets' => $widgets,
                ]],
            ],
        ]];

        $linhaAnexos = "\n\n📎 *Anexos:* {$qtd} " . ($qtd === 1 ? 'foto' : 'fotos');
        if (isset($mensagem['text']) && is_string($mensagem['text'])) {
            $mensagem['text'] .= $linhaAnexos;
        } else {
            $mensagem['text'] = ltrim($linhaAnexos);
        }

        return $mensagem;
    }

    public function enviarNovoAnexoOsNoChat(OpTask $os, OpTaskAnexo $anexo): void
    {
        if (($os->categoria ?? '') !== 'ordem-servico' || empty($os->parent_task_id)) {
            return;
        }

        $pai = OpTask::find($os->parent_task_id)?->fresh();
        if (! $pai) {
            return;
        }

        try {
            $urlImagem = $this->anexos->gerarUrlPublicaChat($os, $anexo);
        } catch (\Throwable) {
            return;
        }

        $codigoOs = trim((string) ($os->taskCode ?? $os->id ?? '')) ?: '—';
        $nomeArquivo = mb_substr(trim((string) ($anexo->nome_arquivo ?? 'Anexo')), 0, 120);

        $mensagem = [
            'text' => "📎 *Novo anexo na OS* `{$codigoOs}`\n{$nomeArquivo}",
            'cardsV2' => [[
                'cardId' => 'os-anexo-' . $anexo->id . '-' . now()->timestamp,
                'card' => [
                    'header' => [
                        'title' => '📎 Anexo da OS',
                        'subtitle' => $codigoOs,
                    ],
                    'sections' => [[
                        'widgets' => [[
                            'image' => $this->montarImagemChatWidget($urlImagem, $nomeArquivo),
                        ]],
                    ]],
                ],
            ]],
        ];

        $this->enviarNotificacao($pai, $mensagem);
    }

    private function timeoutEnvio(array $mensagem): int
    {
        return ! empty($mensagem['cardsV2']) ? 12 : 4;
    }

    /** @return array<string, mixed> */
    private function montarImagemChatWidget(string $imageUrl, string $altText): array
    {
        return [
            'imageUrl' => $imageUrl,
            'altText' => $altText,
            'onClick' => [
                'openLink' => [
                    'url' => $imageUrl,
                ],
            ],
        ];
    }

    public function montarMensagemOsEmAndamento(array $os): array
    {
        $template = $this->mensagens->obterTemplate('ordem-servico', 'Em andamento');
        if ($template !== null) {
            return [
                'text' => $this->mensagens->renderizar($template, $os, null, 'Em andamento'),
            ];
        }

        return $this->montarMensagemOsStatus($os, '📋 *Atualização de Ordem de Serviço*', '🔄', true);
    }

    public function montarMensagemOsFinalizada(array $os): array
    {
        $template = $this->mensagens->obterTemplate('ordem-servico', 'Finalizada');
        if ($template !== null) {
            return [
                'text' => $this->mensagens->renderizar($template, $os, null, 'Finalizada'),
            ];
        }

        return $this->montarMensagemOsStatus($os, '✅ *OS Finalizada*', '✅');
    }

    public function isOsEmAndamento(?string $status): bool
    {
        return $this->normalizarChaveStatus($status) === 'em andamento';
    }

    public function isOsFinalizada(?string $status): bool
    {
        return in_array($this->normalizarChaveStatus($status), ['finalizada', 'finalizar'], true);
    }

    private function montarMensagemOsStatus(array $os, string $titulo, string $statusEmoji, bool $incluirDescricao = false): array
    {
        $nome = trim($os['titulo'] ?? '') ?: '—';
        $status = $this->formatarStatusOs($os['status'] ?? '');
        $tipo = trim($os['os_tipo'] ?? '') ?: $nome;
        $tecnicos = app(TecnicoChatMencaoService::class)->formatarResponsavel($os['responsavel'] ?? '');
        $taskCode = trim($os['taskCode'] ?? '') ?: (string) ($os['id'] ?? '—');
        $parentTitulo = trim($os['parent_titulo'] ?? '');
        $parentCode = trim($os['parent_task_code'] ?? '');

        $linhas = [
            $titulo,
            '━━━━━━━━━━━━━━━━━━━━',
        ];

        if ($parentTitulo !== '' || $parentCode !== '') {
            $refPai = $parentTitulo !== '' ? $parentTitulo : 'Tarefa pai';
            if ($parentCode !== '') {
                $refPai .= " ({$parentCode})";
            }
            $linhas[] = "🔗 *Tarefa pai:* {$refPai}";
        }

        $linhas[] = "📌 *Tipo:* {$tipo}";
        $linhas[] = "👤 *Técnicos:* {$tecnicos}";
        $linhas[] = "{$statusEmoji} *Status da OS:* {$status}";

        if ($incluirDescricao) {
            $descricao = trim($os['descricao'] ?? '') ?: '—';
            $linhas[] = "📝 *Descrição:* {$descricao}";
        }

        $linhas[] = "🔑 *Código OS:* {$taskCode}";

        return ['text' => implode("\n", $linhas)];
    }

    public function montarMensagemTrocaDePoste(array $task, string $statusAnterior, string $statusNovo): array
    {
        return $this->montarMensagemStatus($task, $statusAnterior, $statusNovo);
    }

    public function montarMensagemStatus(array $rompimento, string $statusAnterior, string $statusNovo, ?string $enviadoPor = null): array
    {
        $rompimento = $this->enriquecerTaskComResumoOs($rompimento, $statusNovo);
        $categoria = $this->mensagens->normalizarCategoria($rompimento['categoria'] ?? '');
        $template = $this->mensagens->obterTemplate($categoria, $statusNovo);

        if ($template !== null) {
            $texto = $this->mensagens->renderizar(
                $template,
                $rompimento,
                $statusAnterior,
                $statusNovo,
                $enviadoPor
            );

            return [
                'text' => $this->anexarResumoOsAoTexto($texto, $rompimento, $statusNovo, $template),
            ];
        }

        if ($categoria === 'troca-etiqueta') {
            return $this->anexarResumoOsNaMensagem(
                $this->montarMensagemTrocaDeEtiqueta($rompimento, $statusAnterior, $statusNovo),
                $rompimento,
                $statusNovo
            );
        }

        if ($this->isOtimizacaoRede($rompimento['categoria'] ?? '')) {
            return $this->anexarResumoOsNaMensagem(
                $this->montarMensagemOtimizacaoRede($rompimento, $enviadoPor),
                $rompimento,
                $statusNovo
            );
        }

        if ($this->isRompimento($rompimento['categoria'] ?? '') && $statusNovo === 'Em andamento') {
            return $this->montarMensagemRompimentoEmAndamento($rompimento);
        }

        $tituloAlerta = match ($categoria) {
            'rompimentos', 'rompimento' => 'ROMPIMENTO',
            'troca-poste' => 'TROCA DE POSTE',
            'troca-etiqueta' => 'TROCA DE ETIQUETA',
            'otimizacao-rede' => 'OTIMIZAÇÃO DE REDE',
            'atendimento-cliente' => 'ATENDIMENTO',
            'correcao-atenuacao' => 'CORREÇÃO DE SINAL',
            'manutencao-corretiva' => 'MANUTENÇÃO CORRETIVA',
            default => strtoupper(str_replace('-', ' ', $categoria ?: 'TAREFA')),
        };

        $emoji = match ($statusNovo) {
            'Em andamento' => '🔧',
            'Impedimento' => '🚨',
            'Concluída', 'Finalizada' => '✅',
            default => '📋',
        };

        return [
            'text' => $this->anexarResumoOsAoTexto(
                implode("\n", [
                "{$emoji} *Alerta: {$tituloAlerta}*",
                '━━━━━━━━━━━━━━━━━━━━',
                '💻 *Número da OS:* ' . ($rompimento['numero_os'] ?? $rompimento['ordem_servico'] ?? '—'),
                '📍 *Setor/CTO:* ' . ($rompimento['setor'] ?? '—'),
                '📌 *Região:* ' . ($rompimento['regiao'] ?? '—'),
                "🔄 *Status:* {$statusAnterior} → {$statusNovo}",
                '⚡ *Prioridade:* ' . ($rompimento['prioridade'] ?? '—'),
                '👥 *Clientes afetados:* ' . ($rompimento['clientesAfetados'] ?? '0'),
                '📍 *Coordenadas:* ' . CoordenadasChatFormatter::formatar($rompimento['coordenadas'] ?? ''),
                '📍 *Endereço:* ' . ($rompimento['localizacao_texto'] ?? '—'),
                '🔑 *Código:* ' . ($rompimento['taskCode'] ?? '—'),
            ]),
                $rompimento,
                $statusNovo,
            ),
        ];
    }

    /** @param array<string, mixed> $task */
    private function enriquecerTaskComResumoOs(array $task, string $statusNovo): array
    {
        if (($task['categoria'] ?? '') === 'ordem-servico') {
            return $task;
        }

        if (! $this->osResumo->statusDisparaResumo($statusNovo)) {
            return $task;
        }

        $parentId = (int) ($task['id'] ?? 0);
        if ($parentId <= 0) {
            return $task;
        }

        return array_merge($task, $this->osResumo->paraPayload($parentId));
    }

    /** @param array<string, mixed> $task */
    private function anexarResumoOsAoTexto(string $texto, array $task, string $statusNovo, ?string $template = null): string
    {
        if (! $this->osResumo->statusDisparaResumo($statusNovo)) {
            return $texto;
        }

        if (($task['categoria'] ?? '') === 'ordem-servico') {
            return $texto;
        }

        $resumo = trim((string) ($task['os_resumo'] ?? ''));
        if ($resumo === '') {
            return $texto;
        }

        if ($template !== null && str_contains($template, '{os_resumo}')) {
            return $texto;
        }

        if (str_contains($texto, $resumo)) {
            return $texto;
        }

        return rtrim($texto)."\n\n".$resumo;
    }

    /** @param array<string, mixed> $mensagem @param array<string, mixed> $task */
    private function anexarResumoOsNaMensagem(array $mensagem, array $task, string $statusNovo): array
    {
        if (isset($mensagem['text']) && is_string($mensagem['text'])) {
            $mensagem['text'] = $this->anexarResumoOsAoTexto($mensagem['text'], $task, $statusNovo);
        }

        return $mensagem;
    }

    private function isOtimizacaoRede(string $categoria): bool
    {
        return in_array($categoria, OpTask::CATEGORIAS_OTIMIZACAO_REDE, true);
    }

    private function isRompimento(string $categoria): bool
    {
        return in_array($categoria, ['rompimentos', 'rompimento'], true);
    }

    private function montarMensagemTrocaDeEtiqueta(array $task, string $statusAnterior, string $statusNovo): array
    {
        $itens = TrocaEtiquetaParser::parseItensParaMensagem(
            $task['titulo'] ?? '',
            $task['descricao'] ?? '',
            $task['coordenadas'] ?? '',
            $task['localizacao_texto'] ?? '',
        );

        $emoji = match ($statusNovo) {
            'Em andamento' => '🏷️',
            'Impedimento' => '🚨',
            'Concluída', 'Finalizada' => '✅',
            default => '📋',
        };

        $numeroOs = trim($task['numero_os'] ?? $task['ordem_servico'] ?? '') ?: '—';
        $regiao = trim($task['regiao'] ?? '') ?: '—';
        $responsavel = app(TecnicoChatMencaoService::class)->formatarResponsavel($task['responsavel'] ?? '');
        $taskCode = trim($task['taskCode'] ?? '') ?: (string) ($task['id'] ?? '—');
        $etiquetas = TrocaEtiquetaParser::formatarLocalizacaoLista($itens);

        return [
            'text' => implode("\n", [
                "{$emoji} *TROCA DE ETIQUETA*",
                '━━━━━━━━━━━━━━━━━━━━',
                "💻 *Número da OS:* {$numeroOs}",
                "📌 *Região:* {$regiao}",
                "👤 *Responsável:* {$responsavel}",
                "🔄 *Status:* {$statusAnterior} → {$statusNovo}",
                '',
                '🏷️ *Etiquetas:*',
                $etiquetas,
                '',
                "🔑 *Código:* {$taskCode}",
            ]),
        ];
    }

    private function montarMensagemRompimentoEmAndamento(array $task): array
    {
        $caixa = trim($task['cto'] ?? $task['setor'] ?? '') ?: '—';
        $endereco = trim($task['localizacao_texto'] ?? '') ?: '—';
        $coordenadas = CoordenadasChatFormatter::formatar($task['coordenadas'] ?? '');
        $osHubspot = trim($task['numero_os'] ?? $task['ordem_servico'] ?? '') ?: '—';
        $clientes = trim((string) ($task['clientesAfetados'] ?? '')) !== ''
            ? (string) $task['clientesAfetados']
            : '0';
        $id = trim($task['taskCode'] ?? '') ?: (string) ($task['id'] ?? '—');

        return [
            'text' => implode("\n", [
                "🚨 *ROMPIMENTO - {$caixa}*",
                '',
                "🗺️ *Endereço:* {$endereco}",
                "📍 *Localização inicial:* {$coordenadas}",
                '',
                "🧾 *OS HubSpot:* {$osHubspot}",
                "👥 *Clientes afetados:* {$clientes}",
                "🆔 {$id}",
            ]),
        ];
    }

    private function montarMensagemOtimizacaoRede(array $task, ?string $enviadoPor): array
    {
        $historicoService = app(OpTaskHistoricoService::class);
        $mencaoService = app(TecnicoChatMencaoService::class);
        $titulo = trim($task['titulo'] ?? '') ?: '—';
        $localizacao = $this->formatarLocalizacao($task);
        $descricao = trim($task['descricao'] ?? '') ?: '—';
        $operador = $historicoService->operadorInicioAtividade($task) ?? trim((string) ($enviadoPor ?? ''));
        $enviado = $operador !== '' ? $mencaoService->mencionar($operador) : '—';
        $duracao = $historicoService->formatarDuracaoAtiva(
            $historicoService->calcularDuracaoAtivaMinutos($task)
        );
        $id = trim($task['taskCode'] ?? '') ?: (string) ($task['id'] ?? '—');

        return [
            'text' => implode("\n", [
                'Otimização de Rede',
                "🌐 *{$titulo}*",
                "📍 Localização: {$localizacao}",
                "📝 Descrição: {$descricao}",
                '',
                "👤 Enviado por: {$enviado}",
                "⏱️ Duração ativa: {$duracao}",
                '',
                "🆔 {$id}",
            ]),
        ];
    }

    private function formatarLocalizacao(array $task): string
    {
        $texto = trim($task['localizacao_texto'] ?? '');
        if ($texto !== '') {
            return $texto;
        }

        $coordenadas = trim($task['coordenadas'] ?? '');
        if ($coordenadas !== '') {
            return CoordenadasChatFormatter::formatar($coordenadas);
        }

        return '—';
    }

    private function normalizarChaveStatus(?string $status): string
    {
        return strtolower(str_replace('_', ' ', trim((string) $status)));
    }

    private function formatarStatusOs(?string $status): string
    {
        return match ($this->normalizarChaveStatus($status)) {
            'em andamento' => 'Em andamento',
            'finalizada', 'finalizar' => 'Finalizada',
            'aberta' => 'Aberta',
            'criada' => 'Criada',
            'impedimento' => 'Impedimento',
            default => trim((string) $status) ?: '—',
        };
    }
}
