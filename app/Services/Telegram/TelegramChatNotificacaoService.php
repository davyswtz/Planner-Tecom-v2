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
 * - OS / updates / anexos → comentário na discussão do post
 * - Tarefas antigas / IDs stale → recria o post pai e ancora a OS no comentário
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
        $discussionChatId = $this->resolverDiscussionChatId($regiao, $channelId);
        $ids = $this->lerIds($tarefa);

        // Já existe post pai → OS / update / anexo como comentário.
        if ($ids['channel'] > 0 || $ids['discussion'] > 0) {
            try {
                $ids = $this->assegurarRaizComentarios($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarComentario($discussionChatId, $channelId, $ids, $texto);
                $this->logComentarioOk($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);

                return;
            } catch (Throwable $e) {
                if (! $this->erroIndicaThreadInvalida($e)) {
                    throw $e;
                }

                Log::warning('Telegram canal: thread inválida/stale — recriando post pai', [
                    'task_id' => $tarefa->id,
                    'channel_message_id' => $ids['channel'],
                    'discussion_message_id' => $ids['discussion'],
                    'erro' => $e->getMessage(),
                ]);
                $this->limparIds($tarefa);

                $ids = $this->criarPostPai(
                    $tarefa,
                    $channelId,
                    $discussionChatId,
                    $this->montarAncoraPai($tarefa),
                );
                $this->enviarComentario($discussionChatId, $channelId, $ids, $texto);
                $this->logComentarioOk($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);

                return;
            }
        }

        // 1ª notificação (tarefa antiga sem IDs Telegram, ou nova).
        $lock = null;
        $lockAdquirido = false;
        try {
            $lock = Cache::lock($this->cacheKey((int) $tarefa->id).'_criar', 30);
            $lockAdquirido = $lock->get();
        } catch (Throwable) {
            $lock = null;
            $lockAdquirido = true;
        }

        if ($lock !== null && ! $lockAdquirido) {
            usleep(600_000);
            $tarefa = $tarefa->fresh() ?? $tarefa;
            $ids = $this->lerIds($tarefa);
            if ($ids['channel'] > 0 || $ids['discussion'] > 0) {
                $ids = $this->assegurarRaizComentarios($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarComentario($discussionChatId, $channelId, $ids, $texto);
                $this->logComentarioOk($tarefa, $channelId, $discussionChatId, $ids);
                $this->enviarAnexos($discussionChatId, $channelId, $ids, $anexos);

                return;
            }
        }

        try {
            $ids = $this->criarPostPai($tarefa, $channelId, $discussionChatId, $texto);
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
     * @return array{channel: int, discussion: int}
     */
    private function criarPostPai(
        OpTask $tarefa,
        int|string $channelId,
        int|string $discussionChatId,
        string $texto,
    ): array {
        $updateIdAntes = 0;
        try {
            $updateIdAntes = $this->telegram->obterUltimoUpdateId();
        } catch (Throwable $e) {
            Log::debug('Telegram canal: não drenou updates antes do post', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }

        $criada = $this->telegram->enviarMensagem($channelId, $texto);
        $channelMessageId = (int) ($criada['message_id'] ?? 0);
        if ($channelMessageId <= 0) {
            throw new RuntimeException('Telegram sendMessage no canal sem message_id.');
        }

        $this->salvarIds($tarefa, $channelMessageId, null);

        $discussionMsgId = null;
        try {
            $discussionMsgId = $this->telegram->aguardarMensagemDiscussao(
                $channelId,
                $channelMessageId,
                $discussionChatId,
                12,
                350,
                $updateIdAntes,
            );
        } catch (Throwable $e) {
            Log::debug('Telegram canal: getUpdates do forward falhou', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }

        // Sem forward: abre a thread com reply_parameters e usa esse msg como raiz dos comentários.
        if ($discussionMsgId === null || $discussionMsgId <= 0) {
            try {
                $seed = $this->telegram->enviarMensagem(
                    $discussionChatId,
                    '💬',
                    null,
                    $channelMessageId,
                    $channelId,
                );
                $seedId = (int) ($seed['message_id'] ?? 0);
                if ($seedId > 0) {
                    $discussionMsgId = $seedId;
                }
            } catch (Throwable $e) {
                Log::warning('Telegram canal: post pai ok, mas thread de comentários não abriu', [
                    'task_id' => $tarefa->id,
                    'channel_message_id' => $channelMessageId,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if ($discussionMsgId !== null && $discussionMsgId > 0) {
            $this->salvarIds($tarefa, $channelMessageId, $discussionMsgId);
        }

        Log::info('Telegram canal: post pai criado', [
            'task_id' => $tarefa->id,
            'channel_id' => $channelId,
            'channel_message_id' => $channelMessageId,
            'discussion_chat_id' => $discussionChatId,
            'discussion_message_id' => $discussionMsgId,
        ]);

        return [
            'channel' => $channelMessageId,
            'discussion' => (int) ($discussionMsgId ?? 0),
        ];
    }

    /**
     * @param  array{channel: int, discussion: int}  $ids
     * @return array{channel: int, discussion: int}
     */
    private function assegurarRaizComentarios(
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

        // Tarefas antigas só com channel_id: abre a thread agora.
        $seed = $this->telegram->enviarMensagem(
            $discussionChatId,
            '💬',
            null,
            $ids['channel'],
            $channelId,
        );
        $seedId = (int) ($seed['message_id'] ?? 0);
        if ($seedId > 0) {
            $this->salvarIds($tarefa, $ids['channel'], $seedId);
            $ids['discussion'] = $seedId;
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
        if ($ids['discussion'] > 0) {
            $this->telegram->enviarMensagem($discussionChatId, $texto, null, $ids['discussion']);

            return;
        }

        if ($ids['channel'] <= 0) {
            throw new RuntimeException('Sem telegram_message_id / telegram_topic_id para comentar no post pai.');
        }

        $this->telegram->enviarMensagem(
            $discussionChatId,
            $texto,
            null,
            $ids['channel'],
            $channelId,
        );
    }

    /** @param  array{channel: int, discussion: int}  $ids */
    private function logComentarioOk(
        OpTask $tarefa,
        int|string $channelId,
        int|string $discussionChatId,
        array $ids,
    ): void {
        Log::info('Telegram canal: comentário no post (OS/update)', [
            'task_id' => $tarefa->id,
            'channel_id' => $channelId,
            'discussion_chat_id' => $discussionChatId,
            'channel_message_id' => $ids['channel'],
            'discussion_message_id' => $ids['discussion'],
        ]);
    }

    private function montarAncoraPai(OpTask $tarefa): string
    {
        $titulo = htmlspecialchars(
            trim((string) ($tarefa->titulo ?? '')) ?: 'Tarefa',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $code = htmlspecialchars(
            trim((string) ($tarefa->taskCode ?? '')) ?: (string) ($tarefa->id ?? '—'),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        return "🔗 <b>{$titulo}</b>\n<code>{$code}</code>";
    }

    private function erroIndicaThreadInvalida(Throwable $e): bool
    {
        $msg = mb_strtolower($e->getMessage());

        foreach ([
            'message to be replied not found',
            'message not found',
            'reply message not found',
            'msg_id_invalid',
            'message_id_invalid',
            'message to reply not found',
            'chat not found',
            'message is too old',
            'reply_to_message_id',
        ] as $needle) {
            if (str_contains($msg, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function limparIds(OpTask $tarefa): void
    {
        Cache::forget($this->cacheKey((int) $tarefa->id));

        if (! $tarefa->exists) {
            return;
        }

        $dados = [];
        if ($this->temColuna('telegram_message_id')) {
            $dados['telegram_message_id'] = null;
        }
        if ($this->temColuna('telegram_topic_id')) {
            $dados['telegram_topic_id'] = null;
        }
        if ($dados === []) {
            return;
        }

        try {
            $tarefa->forceFill($dados)->save();
        } catch (Throwable $e) {
            Log::debug('Telegram canal: não limpou IDs no banco', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
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
