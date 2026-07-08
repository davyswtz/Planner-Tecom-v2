<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\Tecnico;
use Illuminate\Support\Facades\Schema;

class TecnicoChatMencaoService
{
    /** @var array<string, string> */
    private array $idsPorChave = [];

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

    public function mencionar(string $nomeOuUsername): string
    {
        $nomeOuUsername = trim($nomeOuUsername);
        if ($nomeOuUsername === '' || $nomeOuUsername === '—') {
            return '—';
        }

        $id = $this->resolverGoogleChatId($nomeOuUsername);
        if ($id !== null) {
            return "<users/{$id}>";
        }

        try {
            return $this->nomes->resolverOuOriginal($nomeOuUsername)['tecnico'];
        } catch (\Throwable) {
            return $nomeOuUsername;
        }
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

    private function carregarIndice(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'google_chat_id')) {
            return;
        }

        foreach (Tecnico::query()->get(['nome', 'username', 'google_chat_id']) as $tecnico) {
            $id = trim((string) ($tecnico->google_chat_id ?? ''));
            if ($id === '') {
                continue;
            }

            foreach ([$tecnico->nome, $tecnico->username] as $alias) {
                $chave = $this->chave((string) $alias);
                if ($chave !== '') {
                    $this->idsPorChave[$chave] = $id;
                }
            }
        }

        foreach (self::ALIASES_PARA_ID as $alias => $canonico) {
            $chaveAlias = $this->chave($alias);
            $chaveCanonico = $this->chave($canonico);
            if ($chaveAlias !== '' && isset($this->idsPorChave[$chaveCanonico])) {
                $this->idsPorChave[$chaveAlias] = $this->idsPorChave[$chaveCanonico];
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
