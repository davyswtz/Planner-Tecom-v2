<?php

namespace App\Services;

use App\Models\AppConfig;
use App\Models\Webhook;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WebhookService
{
    public const REGIAO_PADRAO = 'PADRAO';

    /** @var array<string, string> */
    private array $regiaoParaCodigo = [
        'goval' => 'GOVAL',
        'governador valadares' => 'GOVAL',
        'vale do aço' => 'VALE_DO_ACO',
        'vale do aco' => 'VALE_DO_ACO',
        'caratinga' => 'VALE_DO_ACO', // Caratinga passou a integrar Vale do Aço
        'teste' => 'TESTE',
        'backup' => 'TESTE',
    ];

    /** @var array<string, string> */
    private array $catalogoRegioes = [
        'PADRAO' => 'Padrão',
        'GOVAL' => 'Goval',
        'VALE_DO_ACO' => 'Vale do Aço',
        'TESTE' => 'Teste',
    ];

    public function tabelaDisponivel(): bool
    {
        return Schema::hasTable('webhooks');
    }

    public function listar(): Collection
    {
        if (! $this->tabelaDisponivel()) {
            return collect();
        }

        return Webhook::query()
            ->orderByRaw("CASE regiao WHEN 'PADRAO' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->get();
    }

    public function resolverUrlPorRegiao(?string $regiao): ?string
    {
        if (! $this->tabelaDisponivel()) {
            return $this->resolverUrlLegado($regiao);
        }

        $codigo = $this->normalizarRegiaoParaCodigo($regiao);

        if ($codigo) {
            $url = Webhook::query()
                ->where('regiao', $codigo)
                ->where('ativo', true)
                ->value('url');

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        $padrao = Webhook::query()
            ->where('regiao', self::REGIAO_PADRAO)
            ->where('ativo', true)
            ->value('url');

        return is_string($padrao) && $padrao !== '' ? $padrao : null;
    }

    public function montarConfigLegacy(): array
    {
        if (! $this->tabelaDisponivel()) {
            return AppConfig::getJson('webhookConfig');
        }

        $webhooks = $this->listar();
        $url = '';
        $urlsByRegion = [];

        foreach ($webhooks as $webhook) {
            if ($webhook->regiao === self::REGIAO_PADRAO) {
                $url = $webhook->url;
                continue;
            }

            $urlsByRegion[$webhook->regiao] = $webhook->url;
        }

        return [
            'url' => $url,
            'urlsByRegion' => $urlsByRegion,
            'events' => AppConfig::getJson('webhookEvents', [
                'andamento' => true,
                'concluida' => true,
                'finalizada' => true,
            ]),
        ];
    }

    public function salvarDeConfigLegacy(array $config): void
    {
        if (! $this->tabelaDisponivel()) {
            AppConfig::setJson('webhookConfig', $config);

            return;
        }

        $urlPadrao = trim((string) ($config['url'] ?? ''));
        if ($urlPadrao !== '') {
            Webhook::updateOrCreate(
                ['regiao' => self::REGIAO_PADRAO],
                ['nome' => $this->catalogoRegioes[self::REGIAO_PADRAO], 'url' => $urlPadrao, 'ativo' => true]
            );
        }

        $urlsByRegion = $config['urlsByRegion'] ?? [];
        if (is_array($urlsByRegion)) {
            foreach ($this->catalogoRegioes as $codigo => $nome) {
                if ($codigo === self::REGIAO_PADRAO) {
                    continue;
                }

                if (! array_key_exists($codigo, $urlsByRegion)) {
                    continue;
                }

                $url = trim((string) $urlsByRegion[$codigo]);
                if ($url === '') {
                    Webhook::query()->where('regiao', $codigo)->delete();
                    continue;
                }

                Webhook::updateOrCreate(
                    ['regiao' => $codigo],
                    ['nome' => $nome, 'url' => $url, 'ativo' => true]
                );
            }
        }

        if (isset($config['events']) && is_array($config['events'])) {
            AppConfig::setJson('webhookEvents', $config['events']);
        }
    }

    public function normalizarRegiaoParaCodigo(?string $regiao): ?string
    {
        $regiao = mb_strtolower(trim((string) $regiao));

        if ($regiao === '') {
            return null;
        }

        if (isset($this->regiaoParaCodigo[$regiao])) {
            return $this->regiaoParaCodigo[$regiao];
        }

        foreach ($this->regiaoParaCodigo as $chave => $codigo) {
            if (str_contains($regiao, $chave)) {
                return $codigo;
            }
        }

        return null;
    }

    public function deveNotificarStatus(string $status): bool
    {
        $events = AppConfig::getJson('webhookEvents', [
            'andamento' => true,
            'concluida' => true,
            'finalizada' => true,
        ]);

        $chave = mb_strtolower(str_replace('_', ' ', trim($status)));

        if (in_array($chave, ['criada', 'pendente', 'backlog', 'aberta'], true)) {
            return false;
        }

        return match ($chave) {
            'em andamento' => (bool) ($events['andamento'] ?? true),
            'concluída', 'concluida', 'concluído', 'concluido' => (bool) ($events['concluida'] ?? true),
            'finalizada', 'finalizar', 'finalizado' => (bool) ($events['finalizada'] ?? true),
            'impedimento', 'validação', 'validacao', 'precisa de adequação', 'precisa de adequacao' => true,
            default => false,
        };
    }

    /** @return array<int, array{id: int, regiao: string, nome: string, url: string, ativo: bool}> */
    public function listarFormatado(): array
    {
        return $this->listar()
            ->map(fn (Webhook $webhook) => [
                'id' => $webhook->id,
                'regiao' => $webhook->regiao,
                'nome' => $webhook->nome,
                'url' => $webhook->url,
                'ativo' => $webhook->ativo,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, regiao: string, nome: string, url_mascarada: string, ativo: bool}> */
    public function listarParaConfiguracao(): array
    {
        return $this->listar()
            ->map(fn (Webhook $webhook) => [
                'id' => $webhook->id,
                'regiao' => $webhook->regiao,
                'nome' => $webhook->nome,
                'url_mascarada' => $this->mascararUrl($webhook->url),
                'ativo' => $webhook->ativo,
            ])
            ->all();
    }

    /** @return array{ok: bool, message: string, status?: int} */
    public function enviarTeste(int $id, string $username): array
    {
        if (! $this->tabelaDisponivel()) {
            return ['ok' => false, 'message' => 'Tabela de webhooks não disponível.'];
        }

        $webhook = Webhook::query()->find($id);

        if (! $webhook || ! $webhook->ativo || trim($webhook->url) === '') {
            return ['ok' => false, 'message' => 'Webhook não encontrado ou inativo.'];
        }

        $mensagem = [
            'text' => implode("\n", [
                '🧪 *Teste de webhook — Planner*',
                "📌 *Região:* {$webhook->nome} ({$webhook->regiao})",
                '👤 *Enviado por:* ' . ($username !== '' ? $username : 'Sistema'),
                '🕐 *Hora:* ' . now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
            ]),
        ];

        try {
            $response = Http::timeout(10)->post($webhook->url, $mensagem);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Mensagem de teste enviada com sucesso para o Google Chat.',
                    'status' => $response->status(),
                ];
            }

            return [
                'ok' => false,
                'message' => 'Google Chat retornou erro HTTP ' . $response->status() . '.',
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Falha ao enviar teste: ' . $e->getMessage(),
            ];
        }
    }

    public function mascararUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '—';
        }

        $parsed = parse_url($url);
        if (! is_array($parsed)) {
            return '***';
        }

        $path = $parsed['path'] ?? '';
        $space = $path !== '' ? basename($path) : 'webhook';
        $query = [];
        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $key = isset($query['key']) ? substr($query['key'], 0, 6) . '***' : '***';
        $token = isset($query['token']) ? substr($query['token'], 0, 6) . '***' : '***';

        return "spaces/{$space}?key={$key}&token={$token}";
    }

    private function resolverUrlLegado(?string $regiao): ?string
    {
        $config = AppConfig::getJson('webhookConfig');
        $urlsByRegion = $config['urlsByRegion'] ?? [];
        $codigo = $this->normalizarRegiaoParaCodigo($regiao);

        if ($codigo && ! empty($urlsByRegion[$codigo])) {
            return $urlsByRegion[$codigo];
        }

        return $config['url'] ?? null;
    }
}
