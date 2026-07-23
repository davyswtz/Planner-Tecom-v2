<?php

namespace App\Services\Telegram;

use App\Services\TecnicoChatMencaoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotService
{
    /**
     * Cria um tópico de fórum no grupo. Retorna message_thread_id.
     *
     * @return array{message_thread_id: int, name?: string, icon_color?: int}
     */
    public function criarTopico(int|string $chatId, string $nome): array
    {
        $nome = trim($nome);
        if ($nome === '') {
            $nome = 'Tarefa';
        }
        // Limite da API Telegram
        $nome = mb_substr($nome, 0, 128);

        $resultado = $this->postJson('createForumTopic', [
            'chat_id' => $chatId,
            'name' => $nome,
        ]);

        $threadId = (int) ($resultado['message_thread_id'] ?? 0);
        if ($threadId <= 0) {
            throw new RuntimeException('Telegram createForumTopic sem message_thread_id.');
        }

        return $resultado;
    }

    /**
     * @param  int|string|null  $replyToChatId  Chat da mensagem respondida (ex.: canal), para comentário na discussão
     * @return array<string, mixed>  Resultado da última (ou única) mensagem enviada
     */
    public function enviarMensagem(
        int|string $chatId,
        string $texto,
        ?int $messageThreadId = null,
        ?int $replyToMessageId = null,
        int|string|null $replyToChatId = null,
    ): array {
        $partes = $this->partirTexto($texto);
        $ultima = [];

        foreach ($partes as $i => $parte) {
            $payload = [
                'chat_id' => $chatId,
                'text' => $parte,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($messageThreadId !== null && $messageThreadId > 0) {
                $payload['message_thread_id'] = $messageThreadId;
            }

            // Só a 1ª parte responde ao post pai (comentário nativo).
            if ($i === 0) {
                $this->aplicarReply($payload, $replyToMessageId, $replyToChatId);
            }

            $ultima = $this->postJson('sendMessage', $payload);
        }

        return $ultima;
    }

    /**
     * Telegram limita texto a 4096 chars; parte sem quebrar tags HTML simples no meio.
     *
     * @return array<int, string>
     */
    private function partirTexto(string $texto): array
    {
        $limite = 4096;
        if (mb_strlen($texto) <= $limite) {
            return [$texto];
        }

        $partes = [];
        $restante = $texto;
        while (mb_strlen($restante) > $limite) {
            $corte = mb_strrpos(mb_substr($restante, 0, $limite), "\n");
            if ($corte === false || $corte < (int) ($limite * 0.5)) {
                $corte = $limite;
            }
            $partes[] = mb_substr($restante, 0, $corte);
            $restante = ltrim(mb_substr($restante, $corte));
        }
        if ($restante !== '') {
            $partes[] = $restante;
        }

        return $partes !== [] ? $partes : [$texto];
    }

    /**
     * Menção que notifica: prefere @username; senão link tg://user?id=.
     */
    public static function formatarMencao(int $userId, string $nome, ?string $username = null): string
    {
        $username = ltrim(trim((string) $username), '@');
        if ($username !== '') {
            return '@'.$username;
        }

        return TecnicoChatMencaoService::htmlMencaoPorId($userId, $nome);
    }

    /**
     * Envia foto (ou documento se não for imagem) para o chat.
     *
     * @param  array{nome_arquivo?: string, mime_type?: string, conteudo: string}  $anexo
     * @param  int|string|null  $replyToChatId  Chat da mensagem respondida (ex.: canal)
     */
    public function enviarAnexo(
        int|string $chatId,
        array $anexo,
        ?int $messageThreadId = null,
        ?string $caption = null,
        ?int $replyToMessageId = null,
        int|string|null $replyToChatId = null,
    ): array {
        $binario = $anexo['conteudo'] ?? '';
        if (! is_string($binario) || $binario === '') {
            throw new RuntimeException('Anexo sem conteúdo binário.');
        }

        $nome = (string) ($anexo['nome_arquivo'] ?? 'anexo.jpg');
        $mime = strtolower((string) ($anexo['mime_type'] ?? 'image/jpeg'));
        $eImagem = str_starts_with($mime, 'image/');

        // sendPhoto: até ~10MB; acima ou não-imagem → sendDocument
        $method = ($eImagem && strlen($binario) <= 9_500_000) ? 'sendPhoto' : 'sendDocument';
        $field = $method === 'sendPhoto' ? 'photo' : 'document';

        $fields = [
            'chat_id' => (string) $chatId,
        ];
        if ($messageThreadId !== null && $messageThreadId > 0) {
            $fields['message_thread_id'] = (string) $messageThreadId;
        }
        if ($caption !== null && $caption !== '') {
            $fields['caption'] = mb_substr($caption, 0, 1024);
            $fields['parse_mode'] = 'HTML';
        }

        $this->aplicarReply($fields, $replyToMessageId, $replyToChatId, asMultipart: true);

        return $this->postMultipart($method, $field, $binario, $nome, $fields);
    }

    /**
     * Reage a uma mensagem (bots: 1 emoji por mensagem).
     * Emojis permitidos pela API: ✅ 🚀 👍 🎉 🔥 etc.
     */
    public function reagirMensagem(
        int|string $chatId,
        int $messageId,
        string $emoji,
        bool $isBig = true,
    ): bool {
        if ($messageId <= 0 || $emoji === '') {
            return false;
        }

        $this->postJson('setMessageReaction', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reaction' => [
                ['type' => 'emoji', 'emoji' => $emoji],
            ],
            'is_big' => $isBig,
        ]);

        return true;
    }

    /** @return array<string, mixed> */
    public function getChat(int|string $chatId): array
    {
        return $this->getJson('getChat', ['chat_id' => $chatId]);
    }

    /**
     * linked_chat_id do canal (= grupo de discussão) ou do grupo (= canal vinculado).
     */
    public function linkedChatId(int|string $chatId): ?int
    {
        $chat = $this->getChat($chatId);
        $linked = $chat['linked_chat_id'] ?? null;

        return $linked !== null && $linked !== '' ? (int) $linked : null;
    }

    /**
     * Consome updates antigos e devolve o último update_id.
     * Chamar ANTES de postar no canal, para o poll seguinte só ver o forward novo.
     */
    public function obterUltimoUpdateId(): int
    {
        $last = 0;

        for ($i = 0; $i < 50; $i++) {
            $params = [
                'timeout' => 0,
                'limit' => 100,
            ];
            if ($last > 0) {
                $params['offset'] = $last + 1;
            }

            $updates = $this->getJson('getUpdates', $params);
            if ($updates === []) {
                break;
            }

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }
                $last = max($last, (int) ($update['update_id'] ?? 0));
            }
        }

        if ($last > 0) {
            // Confirma consumo até a ponta da fila.
            $this->getJson('getUpdates', [
                'offset' => $last + 1,
                'limit' => 1,
                'timeout' => 0,
            ]);
        }

        return $last;
    }

    /**
     * Após postar no canal, espera o forward automático no grupo de discussão
     * e devolve o message_id desse forward (raiz dos comentários / OS).
     *
     * @param  int  $updateIdMinimo  Último update_id conhecido ANTES do post (via obterUltimoUpdateId)
     */
    public function aguardarMensagemDiscussao(
        int|string $channelId,
        int $channelMessageId,
        int|string $discussionChatId,
        int $tentativas = 25,
        int $esperaMs = 400,
        int $updateIdMinimo = 0,
    ): ?int {
        if ($channelMessageId <= 0) {
            return null;
        }

        $offset = $updateIdMinimo > 0 ? $updateIdMinimo + 1 : 0;

        for ($i = 0; $i < $tentativas; $i++) {
            $params = [
                'timeout' => 0,
                'limit' => 100,
            ];
            if ($offset > 0) {
                $params['offset'] = $offset;
            }

            $updates = $this->getJson('getUpdates', $params);

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }

                $uid = (int) ($update['update_id'] ?? 0);
                if ($uid >= $offset) {
                    $offset = $uid + 1;
                }

                $msg = $update['message'] ?? null;
                if (! is_array($msg)) {
                    continue;
                }
                if ((string) ($msg['chat']['id'] ?? '') !== (string) $discussionChatId) {
                    continue;
                }

                if (! $this->mensagemEForwardDoCanal($msg, $channelId, $channelMessageId)) {
                    continue;
                }

                $id = (int) ($msg['message_id'] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }

            usleep($esperaMs * 1000);
        }

        return null;
    }

    /**
     * Tenta achar o forward de um post antigo ainda na fila de updates (recuperação).
     */
    public function buscarForwardDiscussaoEmUpdates(
        int|string $channelId,
        int $channelMessageId,
        int|string $discussionChatId,
    ): ?int {
        if ($channelMessageId <= 0) {
            return null;
        }

        $offset = 0;
        for ($i = 0; $i < 30; $i++) {
            $params = ['timeout' => 0, 'limit' => 100];
            if ($offset > 0) {
                $params['offset'] = $offset;
            }

            $updates = $this->getJson('getUpdates', $params);
            if ($updates === []) {
                break;
            }

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }
                $uid = (int) ($update['update_id'] ?? 0);
                if ($uid >= $offset) {
                    $offset = $uid + 1;
                }

                $msg = $update['message'] ?? null;
                if (! is_array($msg)) {
                    continue;
                }
                if ((string) ($msg['chat']['id'] ?? '') !== (string) $discussionChatId) {
                    continue;
                }
                if (! $this->mensagemEForwardDoCanal($msg, $channelId, $channelMessageId)) {
                    continue;
                }

                $id = (int) ($msg['message_id'] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $msg */
    private function mensagemEForwardDoCanal(array $msg, int|string $channelId, int $channelMessageId): bool
    {
        $fwdMsgId = $this->extrairForwardMessageId($msg);
        $fwdChatId = $this->extrairForwardChatId($msg);

        if ($fwdMsgId === $channelMessageId && (string) $fwdChatId === (string) $channelId) {
            return true;
        }

        // Fallback: forward automático do canal (alguns clients só marcam is_automatic_forward).
        if (($msg['is_automatic_forward'] ?? false) === true) {
            $fromChat = $msg['forward_from_chat']['id']
                ?? $msg['forward_origin']['chat']['id']
                ?? null;
            if ((string) $fromChat === (string) $channelId) {
                $fwdId = $this->extrairForwardMessageId($msg);

                return $fwdId === 0 || $fwdId === $channelMessageId;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function aplicarReply(
        array &$payload,
        ?int $replyToMessageId,
        int|string|null $replyToChatId,
        bool $asMultipart = false,
    ): void {
        if ($replyToMessageId === null || $replyToMessageId <= 0) {
            return;
        }

        // Comentário nativo no canal: reply no grupo de discussão apontando para o post do canal.
        if ($replyToChatId !== null && $replyToChatId !== '' && $replyToChatId !== 0 && $replyToChatId !== '0') {
            $params = [
                'message_id' => $replyToMessageId,
                'chat_id' => is_numeric($replyToChatId) ? (int) $replyToChatId : (string) $replyToChatId,
            ];
            $payload['reply_parameters'] = $asMultipart ? json_encode($params) : $params;

            return;
        }

        $payload['reply_to_message_id'] = $asMultipart ? (string) $replyToMessageId : $replyToMessageId;
        $payload['allow_sending_without_reply'] = $asMultipart ? 'true' : true;
    }

    /** @param  array<string, mixed>  $msg */
    private function extrairForwardMessageId(array $msg): int
    {
        if (isset($msg['forward_origin']['message_id'])) {
            return (int) $msg['forward_origin']['message_id'];
        }

        return (int) ($msg['forward_from_message_id'] ?? 0);
    }

    /** @param  array<string, mixed>  $msg */
    private function extrairForwardChatId(array $msg): int|string|null
    {
        if (isset($msg['forward_origin']['chat']['id'])) {
            return $msg['forward_origin']['chat']['id'];
        }

        return $msg['forward_from_chat']['id'] ?? null;
    }

    /** @param  array<string, mixed>  $query */
    private function getJson(string $method, array $query = []): array
    {
        $response = Http::timeout((int) config('services.telegram.timeout', 30))
            ->withOptions(['verify' => (bool) config('services.http_verify_ssl', true)])
            ->get($this->url($method), $query);

        return $this->parseResponse($method, $response);
    }

    /** @param  array<string, mixed>  $payload */
    private function postJson(string $method, array $payload): array
    {
        $response = Http::timeout((int) config('services.telegram.timeout', 30))
            ->withOptions(['verify' => (bool) config('services.http_verify_ssl', true)])
            ->asJson()
            ->post($this->url($method), $payload);

        return $this->parseResponse($method, $response);
    }

    /** @param  array<string, string>  $fields */
    private function postMultipart(
        string $method,
        string $fileField,
        string $binario,
        string $filename,
        array $fields,
    ): array {
        $timeout = max(60, (int) config('services.telegram.timeout', 30));

        $response = Http::timeout($timeout)
            ->withOptions(['verify' => (bool) config('services.http_verify_ssl', true)])
            ->attach($fileField, $binario, $filename)
            ->post($this->url($method), $fields);

        return $this->parseResponse($method, $response);
    }

    private function url(string $method): string
    {
        $token = (string) config('services.telegram.bot_token', '');
        if ($token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN não configurado.');
        }

        $base = rtrim((string) config('services.telegram.api_base', 'https://api.telegram.org'), '/');

        return "{$base}/bot{$token}/{$method}";
    }

    private function parseResponse(string $method, \Illuminate\Http\Client\Response $response): array
    {
        if (! $response->successful()) {
            throw new RequestException($response);
        }

        $json = $response->json();
        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            $desc = is_array($json) ? (string) ($json['description'] ?? 'resposta inválida') : 'resposta inválida';
            throw new RuntimeException("Telegram API ({$method}): {$desc}");
        }

        $result = $json['result'] ?? [];

        return is_array($result) ? $result : [];
    }
}
