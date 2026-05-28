<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class GoogleChatService
{
    public function enviarNotificacao(OpTask $rompimento, array $mensagem): void
{
    $webhook = Webhook::where('regiao', $rompimento->regiao)
        ->where('ativo', true)
        ->first();

    if (!$webhook) return;

    // já tem tópico pai → responde dentro dele
    if ($rompimento->chat_thread_key) {
        $mensagem['thread'] = ['name' => $rompimento->chat_thread_key];
        $response = Http::timeout(4)->post($webhook->url . '&messageReplyOption=REPLY_MESSAGE_FALLBACK_TO_NEW_THREAD', $mensagem);
        \Log::info('Resposta reply:', ['status' => $response->status(), 'body' => $response->body()]);
        return;
    }

    // primeira mensagem → cria tópico pai sem parâmetro extra
    $response = Http::timeout(4)->post($webhook->url, $mensagem);
    \Log::info('Resposta primeira mensagem:', ['status' => $response->status(), 'body' => $response->body()]);

    if ($response->successful()) {
        $threadName = $response->json('thread.name');
        if ($threadName) {
            $rompimento->update(['chat_thread_key' => $threadName]);
        }
    }
}

    public function montarMensagemStatus(array $rompimento, string $statusAnterior, string $statusNovo): array
    {
        $emoji = match($statusNovo) {
            'Em andamento' => '🔧',
            'Impedimento'  => '🚨',
            'Finalizada'   => '✅',
            default        => '📋'
        };

        return [
            'text' => implode("\n", [
                "{$emoji} *Alerta: ROMPIMENTO*",
                "━━━━━━━━━━━━━━━━━━━━",
                "💻 *Número da OS:* " . ($rompimento['numero_os'] ?? '—'),
                "📍 *CTO:* " . ($rompimento['cto'] ?? '—'),
                "📌 *Região:* " . ($rompimento['regiao'] ?? '—'),
                "🔄 *Status:* {$statusAnterior} → {$statusNovo}",
                "⚡ *Prioridade:* " . ($rompimento['prioridade'] ?? '—'),
                "👥 *Clientes afetados:* " . ($rompimento['clientesAfetados'] ?? '0'),
                "📍 *Coordenadas:* " . ($rompimento['coordenadas'] ?? '—'),
                "📍 *Endereço:* " . ($rompimento['localizacao_texto'] ?? '—'),
                "🔑 *Código:* " . ($rompimento['taskCode'] ?? '—'),
            ])
        ];
    }
}