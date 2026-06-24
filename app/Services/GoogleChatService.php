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

    public function montarMensagemOsEmAndamento(array $os): array
    {
        return $this->montarMensagemOsStatus($os, '📋 *Atualização de Ordem de Serviço*', '🔄', true);
    }

    public function montarMensagemOsFinalizada(array $os): array
    {
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
        $tecnico = trim($os['responsavel'] ?? '') ?: '—';

        $linhas = [
            $titulo,
            '',
            "📌 *Nome da OS:* {$nome}",
            "{$statusEmoji} *Status da OS:* {$status}",
        ];

        if ($incluirDescricao) {
            $descricao = trim($os['descricao'] ?? '') ?: '—';
            $linhas[] = "📝 *Descrição:* {$descricao}";
        }

        $linhas[] = "👨‍🔧 *Téc. responsável:* {$tecnico}";

        return ['text' => implode("\n", $linhas)];
    }

    public function montarMensagemStatus(array $rompimento, string $statusAnterior, string $statusNovo, ?string $enviadoPor = null): array
    {
        $categoria = $rompimento['categoria'] ?? '';

        if ($this->isOtimizacaoRede($categoria)) {
            return $this->montarMensagemOtimizacaoRede($rompimento, $enviadoPor);
        }

        if ($this->isRompimento($categoria) && $statusNovo === 'Em andamento') {
            return $this->montarMensagemRompimentoEmAndamento($rompimento);
        }

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

    private function isOtimizacaoRede(string $categoria): bool
    {
        return in_array($categoria, OpTask::CATEGORIAS_OTIMIZACAO_REDE, true);
    }

    private function isRompimento(string $categoria): bool
    {
        return in_array($categoria, ['rompimentos', 'rompimento'], true);
    }

    private function montarMensagemRompimentoEmAndamento(array $task): array
    {
        $caixa = trim($task['cto'] ?? $task['setor'] ?? '') ?: '—';
        $endereco = trim($task['localizacao_texto'] ?? '') ?: '—';
        $coordenadas = trim($task['coordenadas'] ?? '') ?: '—';
        $tecnico = trim($task['responsavel'] ?? '') ?: '—';
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
                "👨‍🔧 *Técnico Responsável:* {$tecnico}",
                '',
                "🧾 *OS HubSpot:* {$osHubspot}",
                "👥 *Clientes afetados:* {$clientes}",
                "🆔 {$id}",
            ]),
        ];
    }

    private function montarMensagemOtimizacaoRede(array $task, ?string $enviadoPor): array
    {
        $titulo = trim($task['titulo'] ?? '') ?: '—';
        $localizacao = $this->formatarLocalizacao($task);
        $descricao = trim($task['descricao'] ?? '') ?: '—';
        $tecnico = trim($task['responsavel'] ?? '') ?: '—';
        $enviado = trim($enviadoPor ?? '') ?: '—';
        $id = trim($task['taskCode'] ?? '') ?: (string) ($task['id'] ?? '—');

        return [
            'text' => implode("\n", [
                'Otimização de Rede',
                "🌐 *{$titulo}*",
                "📍 Localização: {$localizacao}",
                "📝 Descrição: {$descricao}",
                '',
                "👨‍🔧 Técnico Responsável: {$tecnico}",
                "👤 Enviado por: {$enviado}",
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
            return $coordenadas;
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
