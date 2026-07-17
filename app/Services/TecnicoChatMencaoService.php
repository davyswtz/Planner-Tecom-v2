<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\Tecnico;
use Illuminate\Support\Facades\Schema;

/**
 * Menções de técnicos nas mensagens do Planner.
 *
 * Canal principal: Nicon (@nicon_mention_name via nicon_user_id).
 * Fallback: Google Chat (<users/google_chat_id>) quando não há mapeamento Nicon.
 * No envio, cada canal adapta o texto (Nicon ↔ Google).
 */
class TecnicoChatMencaoService
{
    /** @var array<string, string> google chat ids por chave de nome/username */
    private array $idsPorChave = [];

    /** @var array<string, int> nicon user ids por chave de nome/username */
    private array $niconIdsPorChave = [];

    /** @var array<string, string> nome de menção Nicon por google_chat_id */
    private array $nomePorGoogleId = [];

    /** @var array<string, int> nicon_user_id por google_chat_id */
    private array $niconPorGoogleId = [];

    /** @var array<int, string> nome de menção Nicon por nicon_user_id */
    private array $nomePorNiconId = [];

    /** @var array<int, string> google_chat_id por nicon_user_id */
    private array $googlePorNiconId = [];

    /** @var array<string, string> */
    private const ALIASES_PARA_ID = [
        'roberto kallyl' => 'kallyl',
        'gabriel cantao' => 'gabriel cantão',
    ];

    public function __construct(
        private TecnicoNomeResolver $nomes,
    ) {
        $this->carregarIndice();
    }

    public function formatarResponsavel(?string $valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '' || $valor === '—') {
            return '—';
        }

        $partes = OpTask::parseResponsaveis($valor);
        if ($partes === []) {
            return '—';
        }

        return implode(', ', array_map(fn (string $parte) => $this->mencionar($parte), $partes));
    }

    /**
     * Menção padrão nas mensagens do sistema: mapeamento Nicon quando existir.
     */
    public function mencionar(string $nomeOuUsername): string
    {
        $nomeOuUsername = trim($nomeOuUsername);
        if ($nomeOuUsername === '' || $nomeOuUsername === '—') {
            return '—';
        }

        if ($this->resolverNiconUserId($nomeOuUsername) !== null) {
            return $this->mencionarNicon($nomeOuUsername);
        }

        $googleId = $this->resolverGoogleChatId($nomeOuUsername);
        if ($googleId !== null) {
            return "<users/{$googleId}>";
        }

        return $this->nomeExibicao($nomeOuUsername);
    }

    /** Menção no formato Nicon Chat: @Nome Completo */
    public function mencionarNicon(string $nomeOuUsername): string
    {
        $nomeOuUsername = trim($nomeOuUsername);
        if ($nomeOuUsername === '' || $nomeOuUsername === '—') {
            return '—';
        }

        $niconId = $this->resolverNiconUserId($nomeOuUsername);
        if ($niconId !== null) {
            $nome = $this->nomePorNiconId[$niconId] ?? $this->nomeExibicao($nomeOuUsername);

            return '@' . $nome;
        }

        return $this->nomeExibicao($nomeOuUsername);
    }

    public function formatarResponsavelNicon(?string $valor): string
    {
        return $this->formatarResponsavel($valor);
    }

    /**
     * Garante menções no formato Nicon (@Nome).
     * Converte restos de &lt;users/ID&gt; do Google Chat.
     */
    public function adaptarTextoParaNicon(string $texto): string
    {
        return (string) preg_replace_callback(
            '/<users\/([^>]+)>/',
            function (array $m): string {
                $googleId = $m[1];
                $niconId = $this->niconPorGoogleId[$googleId] ?? null;
                if ($niconId !== null) {
                    $nome = $this->nomePorNiconId[$niconId] ?? null;
                    if ($nome) {
                        return '@' . $nome;
                    }
                }

                $nomeGoogle = $this->nomePorGoogleId[$googleId] ?? null;

                return $nomeGoogle !== null ? $nomeGoogle : $m[0];
            },
            $texto
        );
    }

    /**
     * Converte menções Nicon (@Nome) para o formato Google Chat (&lt;users/ID&gt;).
     */
    public function adaptarTextoParaGoogle(string $texto): string
    {
        $pares = [];
        foreach ($this->nomePorNiconId as $niconId => $nome) {
            $googleId = $this->googlePorNiconId[$niconId] ?? null;
            if ($googleId === null || $nome === '') {
                continue;
            }
            $pares['@' . $nome] = '<users/' . $googleId . '>';
        }

        uksort($pares, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($pares as $de => $para) {
            $texto = str_replace($de, $para, $texto);
        }

        return $texto;
    }

    /** @return list<int> */
    public function extrairNiconUserIdsDoTexto(string $texto): array
    {
        $ids = [];

        if (preg_match_all('/<users\/([^>]+)>/', $texto, $matches)) {
            foreach ($matches[1] as $googleId) {
                $niconId = $this->niconPorGoogleId[$googleId] ?? null;
                if ($niconId !== null) {
                    $ids[] = $niconId;
                }
            }
        }

        foreach ($this->nomePorNiconId as $niconId => $nome) {
            if ($nome !== '' && str_contains($texto, '@' . $nome)) {
                $ids[] = $niconId;
            }
        }

        return array_values(array_unique($ids));
    }

    public function resolverGoogleChatId(string $nomeOuUsername): ?string
    {
        $chave = $this->chave($nomeOuUsername);
        if ($chave === '') {
            return null;
        }

        $chave = self::ALIASES_PARA_ID[$chave] ?? $chave;
        if (isset($this->idsPorChave[$chave])) {
            return $this->idsPorChave[$chave];
        }

        $resolvido = $this->nomes->resolver($nomeOuUsername);
        if ($resolvido !== null) {
            $chaveCanonica = $this->chave($resolvido['nome']);

            return $this->idsPorChave[$chaveCanonica] ?? null;
        }

        return null;
    }

    public function resolverNiconUserId(string $nomeOuUsername): ?int
    {
        $chave = $this->chave($nomeOuUsername);
        if ($chave === '') {
            return null;
        }

        $chave = self::ALIASES_PARA_ID[$chave] ?? $chave;
        if (isset($this->niconIdsPorChave[$chave])) {
            return $this->niconIdsPorChave[$chave];
        }

        $resolvido = $this->nomes->resolver($nomeOuUsername);
        if ($resolvido !== null) {
            $chaveCanonica = $this->chave($resolvido['nome']);

            return $this->niconIdsPorChave[$chaveCanonica] ?? null;
        }

        return null;
    }

    private function nomeExibicao(string $nomeOuUsername): string
    {
        try {
            return $this->nomes->resolverOuOriginal($nomeOuUsername)['tecnico'];
        } catch (\Throwable) {
            return $nomeOuUsername;
        }
    }

    private function carregarIndice(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        $temGoogle = Schema::hasColumn('tecnicos', 'google_chat_id');
        $temNicon = Schema::hasColumn('tecnicos', 'nicon_user_id');

        if (! $temGoogle && ! $temNicon) {
            return;
        }

        $cols = ['nome', 'username'];
        if ($temGoogle) {
            $cols[] = 'google_chat_id';
        }
        if ($temNicon) {
            $cols[] = 'nicon_user_id';
        }
        $temMentionName = Schema::hasColumn('tecnicos', 'nicon_mention_name');
        if ($temMentionName) {
            $cols[] = 'nicon_mention_name';
        }

        foreach (Tecnico::query()->get($cols) as $tecnico) {
            $nome = trim((string) ($tecnico->nome ?? ''));
            $mentionName = $temMentionName
                ? trim((string) ($tecnico->nicon_mention_name ?? ''))
                : '';
            if ($mentionName === '') {
                $mentionName = $nome;
            }
            $googleId = $temGoogle ? trim((string) ($tecnico->google_chat_id ?? '')) : '';
            $niconId = $temNicon ? (int) ($tecnico->nicon_user_id ?? 0) : 0;

            if ($googleId !== '') {
                $this->nomePorGoogleId[$googleId] = $mentionName !== '' ? $mentionName : ($nome !== '' ? $nome : $googleId);
            }

            if ($niconId > 0) {
                $this->nomePorNiconId[$niconId] = $mentionName !== '' ? $mentionName : (string) $niconId;
                if ($googleId !== '') {
                    $this->niconPorGoogleId[$googleId] = $niconId;
                    $this->googlePorNiconId[$niconId] = $googleId;
                }
            }

            foreach ([$tecnico->nome, $tecnico->username, $mentionName] as $alias) {
                $chave = $this->chave((string) $alias);
                if ($chave === '') {
                    continue;
                }
                if ($googleId !== '') {
                    $this->idsPorChave[$chave] = $googleId;
                }
                if ($niconId > 0) {
                    $this->niconIdsPorChave[$chave] = $niconId;
                }
            }
        }

        foreach (self::ALIASES_PARA_ID as $alias => $canonico) {
            $chaveAlias = $this->chave($alias);
            $chaveCanonico = $this->chave($canonico);
            if ($chaveAlias === '') {
                continue;
            }
            if (isset($this->idsPorChave[$chaveCanonico])) {
                $this->idsPorChave[$chaveAlias] = $this->idsPorChave[$chaveCanonico];
            }
            if (isset($this->niconIdsPorChave[$chaveCanonico])) {
                $this->niconIdsPorChave[$chaveAlias] = $this->niconIdsPorChave[$chaveCanonico];
            }
        }
    }

    private function chave(string $valor): string
    {
        $valor = mb_strtolower(trim($valor));
        $valor = preg_replace('/\s+/u', ' ', $valor) ?? $valor;

        return $valor;
    }
}
