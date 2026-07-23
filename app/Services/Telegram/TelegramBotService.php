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

    public function enviarMensagem(
        int|string $chatId,
        string $texto,
        ?int $messageThreadId = null,
        ?int $replyToMessageId = null,
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'text' => $texto,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($messageThreadId !== null && $messageThreadId > 0) {
            $payload['message_thread_id'] = $messageThreadId;
        }

        if ($replyToMessageId !== null && $replyToMessageId > 0) {
            $payload['reply_to_message_id'] = $replyToMessageId;
            $payload['allow_sending_without_reply'] = true;
        }

        return $this->postJson('sendMessage', $payload);
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
     */
    public function enviarAnexo(
        int|string $chatId,
        array $anexo,
        ?int $messageThreadId = null,
        ?string $caption = null,
        ?int $replyToMessageId = null,
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
        if ($replyToMessageId !== null && $replyToMessageId > 0) {
            $fields['reply_to_message_id'] = (string) $replyToMessageId;
            $fields['allow_sending_without_reply'] = 'true';
        }
        if ($caption !== null && $caption !== '') {
            $fields['caption'] = mb_substr($caption, 0, 1024);
        }

        return $this->postMultipart($method, $field, $binario, $nome, $fields);
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
     * Após postar no canal, espera o forward automático no grupo de discussão
     * e devolve o message_id desse forward (raiz dos comentários).
     */
    public function aguardarMensagemDiscussao(
        int|string $channelId,
        int $channelMessageId,
        int|string $discussionChatId,
        int $tentativas = 15,
        int $esperaMs = 400,
    ): ?int {
        if ($channelMessageId <= 0) {
            return null;
        }

        for ($i = 0; $i < $tentativas; $i++) {
            $updates = $this->getJson('getUpdates', [
                'timeout' => 0,
                'limit' => 100,
                'allowed_updates' => json_encode(['message']),
            ]);

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }
                $msg = $update['message'] ?? null;
                if (! is_array($msg)) {
                    continue;
                }
                if ((string) ($msg['chat']['id'] ?? '') !== (string) $discussionChatId) {
                    continue;
                }

                $fwdMsgId = $this->extrairForwardMessageId($msg);
                $fwdChatId = $this->extrairForwardChatId($msg);

                if ($fwdMsgId === $channelMessageId && (string) $fwdChatId === (string) $channelId) {
                    $id = (int) ($msg['message_id'] ?? 0);

                    return $id > 0 ? $id : null;
                }
            }

            usleep($esperaMs * 1000);
        }

        return null;
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
