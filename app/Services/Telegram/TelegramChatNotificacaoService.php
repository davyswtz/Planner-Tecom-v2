<?php

namespace App\Services\Telegram;

use App\Models\OpTask;
use App\Services\CoordenadasChatFormatter;
use App\Services\TecnicoChatMencaoService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Telegram canal + comentários nativos (igual Google thread / Nicon):
 * - Tarefa pai → posta no CANAL (guarda telegram_message_id do post)
 * - Resolve o forward automático no grupo de discussão (telegram_topic_id = msg raiz dos comentários)
 * - OS / updates → comentário no grupo de discussão (reply_to_message_id)
 */
class TelegramChatNotificacaoService
{
    public function __construct(
        private TelegramBotService $telegram,
        private WebhookService $webhooks,
        private TecnicoChatMencaoService $mencoes,
    ) {
    }

    /** @param  array<string, mixed>  $mensagem  Payload no formato Google Chat ({ text, nicon_anexos?, ... }) */
    public function enviarNotificacao(OpTask $tarefa, array $mensagem, ?string $statusNovo = null): void
    {
        if (! $this->estaAtivo()) {
            return;
        }

        if (($tarefa->categoria ?? '') === 'tarefas') {
            return;
        }

        if ($statusNovo !== null && ! $this->webhooks->deveNotificarStatus($statusNovo)) {
            return;
        }

        $texto = $this->extrairTexto($mensagem);
        $anexos = $this->extrairAnexos($mensagem);

        if ($texto === '' && $anexos === []) {
            return;
        }

        if ($texto !== '') {
            // Templates: menções Nicon/Google → Telegram (@username ou tg://user?id=) + HTML.
            $texto = CoordenadasChatFormatter::adaptarTextoParaTelegram(
                $this->mencoes->adaptarTextoParaTelegram(
                    $this->mencoes->adaptarTextoParaNicon($texto)
                )
            );
        }

        $regiao = $this->resolverRegiao($tarefa, $mensagem);
        $channelId = $this->resolverChatId($regiao, 'chat_ids', 'chat_id');
        if ($channelId === null) {
            Log::debug('Telegram canal: sem channel_id para região', ['regiao' => $regiao, 'task_id' => $tarefa->id]);

            return;
        }

        try {
            $this->enviar(
                $tarefa->fresh() ?? $tarefa,
                $channelId,
                $regiao,
                $texto !== '' ? $texto : '📎 Anexo',
                $anexos,
            );
        } catch (Throwable $e) {
            Log::warning('Telegram canal: falha ao notificar', [
                'task_id' => $tarefa->id,
                'regiao' => $regiao,
                'channel_id' => $channelId,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function estaAtivo(): bool
    {
        return (bool) config('services.telegram.chat_enabled', false);
    }

    /** @param  array<string, mixed>  $mensagem */
    private function resolverRegiao(OpTask $tarefa, array $mensagem): ?string
    {
        $regiao = trim((string) ($tarefa->regiao ?? ''));
        if ($regiao !== '') {
            return $regiao;
        }

        $doPayload = trim((string) ($mensagem['regiao'] ?? ''));

        return $doPayload !== '' ? $doPayload : null;
    }

    private function resolverChatId(?string $regiao, string $mapKey, string $fallbackKey): int|string|null
    {
        $porRegiao = config("services.telegram.{$mapKey}", []);
        if (! is_array($porRegiao)) {
            $porRegiao = [];
        }

        $chave = $this->normalizarChaveRegiao($regiao);
        if ($chave !== '') {
            foreach ($porRegiao as $nome => $id) {
                if ($this->normalizarChaveRegiao((string) $nome) !== $chave) {
                    continue;
                }
                if ($this->chatIdVazio($id)) {
                    continue;
                }

                return $this->castChatId($id);
            }

            if (in_array($chave, ['goval', 'governador valadares', 'gv'], true)) {
                foreach (['Goval', 'GOVAL', 'goval', 'Governador Valadares'] as $alias) {
                    $id = $porRegiao[$alias] ?? null;
                    if (! $this->chatIdVazio($id)) {
                        return $this->castChatId($id);
                    }
                }
            }

            if (in_array($chave, ['vale do aço', 'vale do aco', 'vale_do_aco', 'va', 'ipatinga', 'caratinga'], true)) {
                foreach (['Vale do Aço', 'Vale do Aco', 'VALE_DO_ACO', 'vale do aço'] as $alias) {
                    $id = $porRegiao[$alias] ?? null;
                    if (! $this->chatIdVazio($id)) {
                        return $this->castChatId($id);
                    }
                }
            }

            if (in_array($chave, ['teste', 'test', 'backup'], true)) {
                foreach (['Teste', 'TESTE', 'teste', 'Backup', 'backup'] as $alias) {
                    $id = $porRegiao[$alias] ?? null;
                    if (! $this->chatIdVazio($id)) {
                        return $this->castChatId($id);
                    }
                }
            }
        }

        $padrao = config("services.telegram.{$fallbackKey}");
        if ($this->chatIdVazio($padrao)) {
            return null;
        }

        return $this->castChatId($padrao);
    }

    private function resolverDiscussionChatId(?string $regiao, int|string $channelId): int|string
    {
        $explicito = $this->resolverChatId($regiao, 'discussion_chat_ids', 'discussion_chat_id');
        if ($explicito !== null) {
            return $explicito;
        }

        try {
            $linked = $this->telegram->linkedChatId($channelId);
            if ($linked !== null && $linked !== 0) {
                return $linked;
            }
        } catch (Throwable $e) {
            Log::debug('Telegram canal: não resolveu linked_chat_id', [
                'channel_id' => $channelId,
                'erro' => $e->getMessage(),
            ]);
        }

        throw new RuntimeException(
            'Grupo de discussão do canal não configurado. Defina TELEGRAM_DISCUSSION_CHAT_IDS ou vincule discussão no canal.'
        );
    }

    private function chatIdVazio(mixed $id): bool
    {
        return $id === null || $id === '' || $id === 0 || $id === '0';
    }

    private function castChatId(mixed $id): int|string
    {
        return is_numeric($id) ? (int) $id : (string) $id;
    }

    private function normalizarChaveRegiao(?string $regiao): string
    {
        $regiao = mb_strtolower(trim((string) $regiao));
        $regiao = str_replace(['_', '-'], ' ', $regiao);

        return preg_replace('/\s+/u', ' ', $regiao) ?? $regiao;
    }

    /** @param  array<string, mixed>  $mensagem */
    private function extrairTexto(array $mensagem): string
    {
        $texto = $mensagem['text'] ?? '';

        return is_string($texto) ? trim($texto) : '';
    }

    /**
     * @param  array<string, mixed>  $mensagem
     * @return array<int, array{nome_arquivo: string, mime_type: string, conteudo: string}>
     */
    private function extrairAnexos(array $mensagem): array
    {
        $anexos = $mensagem['nicon_anexos'] ?? [];
        if (! is_array($anexos) || $anexos === []) {
            return [];
        }

        $validos = [];
        foreach ($anexos as $anexo) {
            if (! is_array($anexo)) {
                continue;
            }
            $conteudo = $anexo['conteudo'] ?? '';
            if (! is_string($conteudo) || $conteudo === '') {
                continue;
            }
            $validos[] = [
                'nome_arquivo' => (string) ($anexo['nome_arquivo'] ?? 'anexo.jpg'),
                'mime_type' => (string) ($anexo['mime_type'] ?? 'image/jpeg'),
                'conteudo' => $conteudo,
            ];
        }

        return $validos;
    }

    /**
     * @param  array<int, array{nome_arquivo: string, mime_type: string, conteudo: string}>  $anexos
     */
    private function enviar(
        OpTask $tarefa,
        int|string $channelId,
        ?string $regiao,
        string $texto,
        array $anexos = [],
    ): void {
        $discussionMsgId = $this->lerDiscussionMessageId($tarefa);

        if ($discussionMsgId > 0) {
            $discussionChatId = $this->resolverDiscussionChatId($regiao, $channelId);
            $criada = $this->telegram->enviarMensagem($discussionChatId, $texto, null, $discussionMsgId);
            $messageId = (int) ($criada['message_id'] ?? 0);

            Log::info('Telegram canal: comentário no post', [
                'task_id' => $tarefa->id,
                'channel_id' => $channelId,
                'discussion_chat_id' => $discussionChatId,
                'reply_to' => $discussionMsgId,
                'message_id' => $messageId,
            ]);

            $this->enviarAnexos($discussionChatId, $discussionMsgId, $anexos);

            return;
        }

        // 1) Post no canal (mensagem pai — aparece com "Deixe um comentário")
        $criada = $this->telegram->enviarMensagem($channelId, $texto);
        $channelMessageId = (int) ($criada['message_id'] ?? 0);
        if ($channelMessageId <= 0) {
            throw new RuntimeException('Telegram sendMessage no canal sem message_id.');
        }

        $discussionChatId = $this->resolverDiscussionChatId($regiao, $channelId);

        // 2) Forward automático no grupo de discussão = raiz dos comentários
        $discussionMsgId = $this->telegram->aguardarMensagemDiscussao(
            $channelId,
            $channelMessageId,
            $discussionChatId,
        );

        if ($discussionMsgId === null || $discussionMsgId <= 0) {
            // Ainda grava o post do canal; comentários falharão até reprocessar.
            $this->salvarIds($tarefa, $channelMessageId, null);
            throw new RuntimeException(
                "Post no canal ok (message_id={$channelMessageId}), mas não achei o forward no grupo de discussão {$discussionChatId}. "
                .'Confirme que o bot está no grupo de discussão e que comentários estão ativos no canal.'
            );
        }

        $this->salvarIds($tarefa, $channelMessageId, $discussionMsgId);

        Log::info('Telegram canal: post criado', [
            'task_id' => $tarefa->id,
            'channel_id' => $channelId,
            'channel_message_id' => $channelMessageId,
            'discussion_chat_id' => $discussionChatId,
            'discussion_message_id' => $discussionMsgId,
        ]);

        $this->enviarAnexos($discussionChatId, $discussionMsgId, $anexos);
    }

    /**
     * @param  array<int, array{nome_arquivo: string, mime_type: string, conteudo: string}>  $anexos
     */
    private function enviarAnexos(int|string $chatId, ?int $replyToMessageId, array $anexos): void
    {
        if ($anexos === []) {
            return;
        }

        foreach ($anexos as $anexo) {
            try {
                $this->telegram->enviarAnexo(
                    $chatId,
                    $anexo,
                    null,
                    mb_substr((string) ($anexo['nome_arquivo'] ?? 'Anexo'), 0, 1024),
                    $replyToMessageId,
                );
            } catch (Throwable $e) {
                Log::warning('Telegram canal: falha ao enviar imagem', [
                    'chat_id' => $chatId,
                    'reply_to' => $replyToMessageId,
                    'arquivo' => $anexo['nome_arquivo'] ?? null,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }

    /** message_id do forward no grupo de discussão (raiz dos comentários) */
    private function lerDiscussionMessageId(OpTask $tarefa): int
    {
        if ($this->temColuna('telegram_topic_id')) {
            $id = (int) ($tarefa->telegram_topic_id ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        $cached = Cache::get($this->cacheKey((int) $tarefa->id), []);

        return (int) ($cached['discussion_message_id'] ?? $cached['topic_id'] ?? 0);
    }

    private function salvarIds(OpTask $tarefa, int $channelMessageId, ?int $discussionMessageId): void
    {
        $cached = Cache::get($this->cacheKey((int) $tarefa->id), []);
        if ($channelMessageId > 0) {
            $cached['channel_message_id'] = $channelMessageId;
            $cached['message_id'] = $channelMessageId;
        }
        if ($discussionMessageId !== null && $discussionMessageId > 0) {
            $cached['discussion_message_id'] = $discussionMessageId;
            $cached['topic_id'] = $discussionMessageId;
        }
        Cache::put($this->cacheKey((int) $tarefa->id), $cached, now()->addDays(30));

        $dados = [];
        if ($channelMessageId > 0 && $this->temColuna('telegram_message_id')) {
            $dados['telegram_message_id'] = $channelMessageId;
        }
        if ($discussionMessageId !== null && $discussionMessageId > 0 && $this->temColuna('telegram_topic_id')) {
            $dados['telegram_topic_id'] = $discussionMessageId;
        }

        if ($dados === [] || ! $tarefa->exists) {
            return;
        }

        try {
            $tarefa->update($dados);
        } catch (Throwable $e) {
            Log::warning('Telegram canal: não gravou IDs no banco (usando cache)', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function temColuna(string $coluna): bool
    {
        static $cache = [];

        if (array_key_exists($coluna, $cache)) {
            return $cache[$coluna];
        }

        try {
            $cache[$coluna] = Schema::hasColumn('op_tasks', $coluna);
        } catch (Throwable) {
            $cache[$coluna] = false;
        }

        return $cache[$coluna];
    }

    private function cacheKey(int $taskId): string
    {
        return "telegram_chat_task_{$taskId}";
    }
}
