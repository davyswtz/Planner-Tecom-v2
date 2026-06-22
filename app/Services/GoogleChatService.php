<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Models\OpTask;
use Illuminate\Support\Facades\Http;

class GoogleChatService
{
    public function __construct(private WebhookService $webhooks)
    {
    }

    public function enviarNotificacao(OpTask $rompimento, array $mensagem, ?string $statusNovo = null): void
    {
        if ($statusNovo !== null && ! $this->webhooks->deveNotificarStatus($statusNovo)) {
            return;
        }

        $url = $this->webhooks->resolverUrlPorRegiao($rompimento->regiao);

        if (! $url) {
            return;
        }

        if ($rompimento->chat_thread_key) {
            $mensagem['thread'] = ['name' => $rompimento->chat_thread_key];
            Http::timeout(4)->post(
                $url . '&messageReplyOption=REPLY_MESSAGE_FALLBACK_TO_NEW_THREAD',
                $mensagem
            );

            return;
        }

        $response = Http::timeout(4)->post($url, $mensagem);

        if ($response->successful()) {
            $threadName = $response->json('thread.name');
            if ($threadName) {
                $rompimento->update(['chat_thread_key' => $threadName]);
            }
        }
    }

    public function montarMensagemStatus(array $rompimento, string $statusAnterior, string $statusNovo): array
    {
        $categoria = $rompimento['categoria'] ?? '';
        $tituloAlerta = match ($categoria) {
            'rompimentos', 'rompimento' => 'ROMPIMENTO',
            'troca-poste' => 'TROCA DE POSTE',
            'otimizacao-rede' => 'OTIMIZAÇÃO DE REDE',
            'atendimento-cliente' => 'ATENDIMENTO',
            default => strtoupper(str_replace('-', ' ', $categoria ?: 'TAREFA')),
        };

        $emoji = match ($statusNovo) {
            'Em andamento' => '🔧',
            'Impedimento' => '🚨',
            'Concluída', 'Finalizada' => '✅',
            default => '📋',
        };

        return [
            'text' => implode("\n", [
                "{$emoji} *Alerta: {$tituloAlerta}*",
                '━━━━━━━━━━━━━━━━━━━━',
                '💻 *Número da OS:* ' . ($rompimento['numero_os'] ?? $rompimento['ordem_servico'] ?? '—'),
                '📍 *Setor/CTO:* ' . ($rompimento['setor'] ?? '—'),
                '📌 *Região:* ' . ($rompimento['regiao'] ?? '—'),
                "🔄 *Status:* {$statusAnterior} → {$statusNovo}",
                '⚡ *Prioridade:* ' . ($rompimento['prioridade'] ?? '—'),
                '👥 *Clientes afetados:* ' . ($rompimento['clientesAfetados'] ?? '0'),
                '📍 *Coordenadas:* ' . ($rompimento['coordenadas'] ?? '—'),
                '📍 *Endereço:* ' . ($rompimento['localizacao_texto'] ?? '—'),
                '🔑 *Código:* ' . ($rompimento['taskCode'] ?? '—'),
            ]),
        ];
    }
}
