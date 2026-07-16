<?php

namespace App\Services\Nicon;

use App\Models\OpTask;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Espelha o fluxo do Google Chat no Nicon, sem substituir webhooks:
 * - 1ª notificação da tarefa pai → mensagem na conversa (guarda id_chat_mensagem)
 * - OS / updates seguintes → reply no tópico (ou POST no chat do tópico)
 *
 * Persistência: colunas nicon_* em op_tasks quando migradas;
 * senão fallback em cache (útil para teste local sem migrate).
 */
class NiconChatNotificacaoService
{
    public function __construct(
        private NiconWebService $nicon,
        private WebhookService $webhooks,
    ) {
    }

    /** @param  array<string, mixed>  $mensagem  Payload no formato Google Chat ({ text, cardsV2?, ... }) */
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
        if ($texto === '') {
            return;
        }

        $conversaId = $this->resolverConversaId($tarefa->regiao);
        if ($conversaId <= 0) {
            Log::warning('Nicon chat: conversa não configurada', ['regiao' => $tarefa->regiao]);

            return;
        }

        try {
            $this->enviar($tarefa->fresh() ?? $tarefa, $conversaId, $texto);
        } catch (Throwable $e) {
            Log::warning('Nicon chat: falha ao notificar', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function estaAtivo(): bool
    {
        return (bool) config('services.nicon.chat_enabled', false);
    }

    private function resolverConversaId(?string $regiao): int
    {
        $porRegiao = config('services.nicon.chat_conversas', []);
        if (is_array($porRegiao) && $regiao !== null && $regiao !== '') {
            $id = (int) ($porRegiao[$regiao] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return (int) config('services.nicon.chat_conversa_id', 0);
    }

    /** @param  array<string, mixed>  $mensagem */
    private function extrairTexto(array $mensagem): string
    {
        $texto = $mensagem['text'] ?? '';

        return is_string($texto) ? trim($texto) : '';
    }

    private function enviar(OpTask $tarefa, int $conversaId, string $texto): void
    {
        $ids = $this->lerIds($tarefa);

        if ($ids['thread'] > 0) {
            $this->nicon->enviarMensagemChat($ids['thread'], $texto);
            Log::info('Nicon chat: update no tópico', [
                'task_id' => $tarefa->id,
                'thread_chat_id' => $ids['thread'],
            ]);

            return;
        }

        if ($ids['raiz'] > 0) {
            $resposta = $this->nicon->enviarMensagemThread($conversaId, $ids['raiz'], $texto);
            $novoThreadChat = (int) data_get($resposta, 'thread.id_chat', 0);
            if ($novoThreadChat > 0) {
                $this->salvarIds($tarefa, $ids['raiz'], $novoThreadChat);
            }
            Log::info('Nicon chat: reply no tópico', [
                'task_id' => $tarefa->id,
                'mensagem_raiz_id' => $ids['raiz'],
                'thread_chat_id' => $novoThreadChat,
            ]);

            return;
        }

        $criada = $this->nicon->enviarMensagemChat($conversaId, $texto);
        $idMensagem = (int) ($criada['id_chat_mensagem'] ?? 0);
        if ($idMensagem > 0) {
            $this->salvarIds($tarefa, $idMensagem, null);
        }
        Log::info('Nicon chat: mensagem pai criada', [
            'task_id' => $tarefa->id,
            'mensagem_raiz_id' => $idMensagem,
            'conversa_id' => $conversaId,
        ]);
    }

    /** @return array{raiz: int, thread: int} */
    private function lerIds(OpTask $tarefa): array
    {
        if ($this->temColunas()) {
            $raiz = (int) ($tarefa->nicon_mensagem_raiz_id ?? 0);
            $thread = (int) ($tarefa->nicon_thread_chat_id ?? 0);
            if ($raiz > 0 || $thread > 0) {
                return ['raiz' => $raiz, 'thread' => $thread];
            }
        }

        $cached = Cache::get($this->cacheKey((int) $tarefa->id), []);

        return [
            'raiz' => (int) ($cached['raiz'] ?? 0),
            'thread' => (int) ($cached['thread'] ?? 0),
        ];
    }

    private function salvarIds(OpTask $tarefa, int $raiz, ?int $thread): void
    {
        $payload = [
            'raiz' => $raiz,
            'thread' => $thread ?? 0,
        ];
        Cache::put($this->cacheKey((int) $tarefa->id), $payload, now()->addDays(30));

        if (! $this->temColunas()) {
            return;
        }

        try {
            $dados = ['nicon_mensagem_raiz_id' => $raiz];
            if ($thread !== null && $thread > 0) {
                $dados['nicon_thread_chat_id'] = $thread;
            }
            $tarefa->update($dados);
        } catch (Throwable $e) {
            Log::warning('Nicon chat: não gravou IDs no banco (usando cache)', [
                'task_id' => $tarefa->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function temColunas(): bool
    {
        static $ok = null;

        if ($ok !== null) {
            return $ok;
        }

        try {
            $ok = Schema::hasColumn('op_tasks', 'nicon_mensagem_raiz_id')
                && Schema::hasColumn('op_tasks', 'nicon_thread_chat_id');
        } catch (Throwable) {
            $ok = false;
        }

        return $ok;
    }

    private function cacheKey(int $taskId): string
    {
        return "nicon_chat_task_{$taskId}";
    }
}
