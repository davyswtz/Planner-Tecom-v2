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
 * - Tarefa pai → posta no CANAL (guarda telegram_message_id)
 * - OS / updates / anexos → comentário na discussão do post (reply_parameters no canal)
 * - Best-effort: também guarda telegram_topic_id (= msg forward na discussão) se getUpdates achar
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

            // Tarefa pai finalizada → ✅ no post do canal + 🚀 no forward da discussão
            if ($this->eTarefaPai($tarefa) && $this->statusFinalizado($statusNovo)) {
                $this->reagirFinalizacao($tarefa->fresh() ?? $tarefa, $channelId, $regiao);
            }
        } catch (Throwable $e) {
            Log::warning('Telegram canal: falha ao notificar', [
                'task_id' => $tarefa->id,
                'regiao' => $regiao,
                'channel_id' => $channelId,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function eTarefaPai(OpTask $tarefa): bool
    {
        return empty($tarefa->parent_task_id);
    }

    private function statusFinalizado(?string $status): bool
    {
        $chave = mb_strtolower(str_replace('_', ' ', trim((string) $status)));

        return in_array($chave, [
            'finalizada',
            'finalizar',
            'finalizado',
            'concluída',
            'concluida',
            'concluído',
            'concluido',
        ], true);
    }

    /**
     * Bot só pode 1 reação por mensagem: certo no canal, foguete na discussão.
     * Se o canal não liberar ✅/🚀, cai para 🎉/🔥 (comum em canais com reações limitadas).
     */
    private function reagirFinalizacao(OpTask $tarefa, int|string $channelId, ?string $regiao): void
    {
        $ids = $this->lerIds($tarefa);
        if ($ids['channel'] <= 0 && $ids['discussion'] <= 0) {
            return;
        }

        try {
            if ($ids['channel'] > 0) {
                $this->reagirComFallback($channelId, $ids['channel'], ['✅', '🎉', '👍']);
            }

            if ($ids['discussion'] > 0) {
                $discussionChatId = $this->resolverDiscussionChatId($regiao, $channelId);
                $this->reagirComFallback($discussionChatId, $ids['discussion'], ['🚀', '🔥', '🏆']);
            }

            Log::info('Telegram canal: reações de finalização', [
                'task_id' => $tarefa->id,
                'channel_message_id' => $ids['channel'],
                'discussion_message_id' => $ids['discussion'],
            ]);
        } catch (Throwable $e) {
            Log::warning('Telegram canal: falha ao reagir na finalização', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /** @param  array<int, string>  $emojis */
    private function reagirComFallback(int|string $chatId, int $messageId, array $emojis): void
    {
        $ultimoErro = null;

        foreach ($emojis as $emoji) {
            try {
                $this->telegram->reagirMensagem($chatId, $messageId, $emoji, true);

                return;
            } catch (Throwable $e) {
                $ultimoErro = $e;
                if (! str_contains($e->getMessage(), 'REACTION_INVALID')) {
                    throw $e;
                }
            }
        }

        if ($ultimoErro !== null) {
            throw $ultimoErro;
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
        $ids = $this->lerIds($tarefa);
        $discussionChatId = $this->resolverDiscussionChatId($regiao, $channelId);

        // Já existe post pai → OS / update / anexo SEMPRE como comentário (nunca post novo no canal).
        if ($ids['channel'] > 0 || $ids['discussion'] > 0) {
            $ids = $this->garantirDiscussionId($tarefa, $channelId, $discussionChatId, $ids);
            $this->enviarComentario($discussionChatId, $channelId, $ids, $texto);

            Log::info('Telegram canal: comentário no post (OS/update)', [
                'task_id' => $tarefa->id,
                'channel_id' => $channelId,
                'discussion_chat_id' => $discussionChatId,
                'channel_message_id' => $ids['channel'],
                'discussion_message_id' => $ids['discussion'],
            ]);

            $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);

            return;
        }

        // Lock evita dois posts pai simultâneos no mesmo task (race no arraste).
        $lock = null;
        $lockAdquirido = false;
        try {
            $lock = Cache::lock($this->cacheKey((int) $tarefa->id).'_criar', 30);
            $lockAdquirido = $lock->get();
        } catch (Throwable) {
            $lock = null;
            $lockAdquirido = true; // segue sem lock se o driver não suportar
        }

        if ($lock !== null && ! $lockAdquirido) {
            usleep(500_000);
            $tarefa = $tarefa->fresh() ?? $tarefa;
            $ids = $this->lerIds($tarefa);
            if ($ids['channel'] > 0 || $ids['discussion'] > 0) {
                $ids = $this->garantirDiscussionId($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarComentario($discussionChatId, $channelId, $ids, $texto);
                $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);

                return;
            }
        }

        try {
            // Limpa fila de updates ANTES do post, senão o forward novo se perde no backlog.
            $updateIdAntes = 0;
            try {
                $updateIdAntes = $this->telegram->obterUltimoUpdateId();
            } catch (Throwable $e) {
                Log::debug('Telegram canal: não drenou updates antes do post', [
                    'task_id' => $tarefa->id,
                    'erro' => $e->getMessage(),
                ]);
            }

            // 1) Post no canal (mensagem pai — "Deixe um comentário")
            $criada = $this->telegram->enviarMensagem($channelId, $texto);
            $channelMessageId = (int) ($criada['message_id'] ?? 0);
            if ($channelMessageId <= 0) {
                throw new RuntimeException('Telegram sendMessage no canal sem message_id.');
            }

            $this->salvarIds($tarefa, $channelMessageId, null);

            // 2) Forward automático na discussão = raiz onde a OS/comentários devem ir
            $discussionMsgId = $this->telegram->aguardarMensagemDiscussao(
                $channelId,
                $channelMessageId,
                $discussionChatId,
                25,
                400,
                $updateIdAntes,
            );

            if ($discussionMsgId === null || $discussionMsgId <= 0) {
                throw new RuntimeException(
                    "Post no canal ok (message_id={$channelMessageId}), mas não achei o forward na discussão {$discussionChatId}. "
                    .'Sem isso a OS não entra como comentário. Confirme bot admin no canal + membro do grupo de discussão.'
                );
            }

            $this->salvarIds($tarefa, $channelMessageId, $discussionMsgId);

            Log::info('Telegram canal: post pai criado', [
                'task_id' => $tarefa->id,
                'channel_id' => $channelId,
                'channel_message_id' => $channelMessageId,
                'discussion_chat_id' => $discussionChatId,
                'discussion_message_id' => $discussionMsgId,
            ]);

            $ids = [
                'channel' => $channelMessageId,
                'discussion' => $discussionMsgId,
            ];
            $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);
        } finally {
            if ($lock !== null && $lockAdquirido) {
                try {
                    $lock->release();
                } catch (Throwable) {
                    // ignore
                }
            }
        }
    }

    /**
     * Garante telegram_topic_id (msg na discussão). Sem ele a OS não vira comentário do canal.
     *
     * @param  array{channel: int, discussion: int}  $ids
     * @return array{channel: int, discussion: int}
     */
    private function garantirDiscussionId(
        OpTask $tarefa,
        int|string $channelId,
        int|string $discussionChatId,
        array $ids,
    ): array {
        if ($ids['discussion'] > 0) {
            return $ids;
        }

        if ($ids['channel'] <= 0) {
            return $ids;
        }

        try {
            $encontrado = $this->telegram->buscarForwardDiscussaoEmUpdates(
                $channelId,
                $ids['channel'],
                $discussionChatId,
            );
            if ($encontrado !== null && $encontrado > 0) {
                $this->salvarIds($tarefa, $ids['channel'], $encontrado);
                $ids['discussion'] = $encontrado;

                return $ids;
            }
        } catch (Throwable $e) {
            Log::debug('Telegram canal: recuperação de discussion_id falhou', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }

        return $ids;
    }

    /** @param  array{channel: int, discussion: int}  $ids */
    private function enviarComentario(
        int|string $discussionChatId,
        int|string $channelId,
        array $ids,
        string $texto,
    ): void {
        // Caminho correto: reply na msg forward da discussão → aparece como comentário do post pai.
        if ($ids['discussion'] > 0) {
            $this->telegram->enviarMensagem($discussionChatId, $texto, null, $ids['discussion']);

            return;
        }

        // Fallback: reply_parameters apontando para o post do canal.
        if ($ids['channel'] <= 0) {
            throw new RuntimeException('Sem telegram_message_id / telegram_topic_id para comentar no post pai.');
        }

        $resposta = $this->telegram->enviarMensagem(
            $discussionChatId,
            $texto,
            null,
            $ids['channel'],
            $channelId,
        );

        // Se a API não vinculou como reply, a OS NÃO entrou no comentário do canal.
        $vinculou = isset($resposta['reply_to_message']) || isset($resposta['external_reply']);
        if (! $vinculou) {
            throw new RuntimeException(
                'Comentário enviado sem vínculo ao post pai (reply_to ausente). '
                .'A OS precisa de telegram_topic_id (forward na discussão).'
            );
        }
    }

    /**
     * @param  array{channel: int, discussion: int}  $ids
     * @param  array<int, array{nome_arquivo: string, mime_type: string, conteudo: string}>  $anexos
     */
    private function enviarAnexos(
        int|string $discussionChatId,
        int|string $channelId,
        array $ids,
        array $anexos,
    ): void {
        if ($anexos === []) {
            return;
        }

        foreach ($anexos as $anexo) {
            try {
                $caption = htmlspecialchars(
                    mb_substr((string) ($anexo['nome_arquivo'] ?? 'Anexo'), 0, 1024),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );

                if ($ids['discussion'] > 0) {
                    $this->telegram->enviarAnexo(
                        $discussionChatId,
                        $anexo,
                        null,
                        $caption,
                        $ids['discussion'],
                    );
                } elseif ($ids['channel'] > 0) {
                    $this->telegram->enviarAnexo(
                        $discussionChatId,
                        $anexo,
                        null,
                        $caption,
                        $ids['channel'],
                        $channelId,
                    );
                } else {
                    $this->telegram->enviarAnexo($discussionChatId, $anexo, null, $caption);
                }
            } catch (Throwable $e) {
                Log::warning('Telegram canal: falha ao enviar imagem', [
                    'chat_id' => $discussionChatId,
                    'channel_message_id' => $ids['channel'] ?? null,
                    'discussion_message_id' => $ids['discussion'] ?? null,
                    'arquivo' => $anexo['nome_arquivo'] ?? null,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return array{channel: int, discussion: int} */
    private function lerIds(OpTask $tarefa): array
    {
        $channel = 0;
        $discussion = 0;

        if ($this->temColuna('telegram_message_id')) {
            $channel = (int) ($tarefa->telegram_message_id ?? 0);
        }
        if ($this->temColuna('telegram_topic_id')) {
            $discussion = (int) ($tarefa->telegram_topic_id ?? 0);
        }

        if ($channel > 0 || $discussion > 0) {
            return ['channel' => $channel, 'discussion' => $discussion];
        }

        $cached = Cache::get($this->cacheKey((int) $tarefa->id), []);

        return [
            'channel' => (int) ($cached['channel_message_id'] ?? $cached['message_id'] ?? 0),
            'discussion' => (int) ($cached['discussion_message_id'] ?? $cached['topic_id'] ?? 0),
        ];
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
