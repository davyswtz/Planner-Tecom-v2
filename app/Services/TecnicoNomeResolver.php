<?php

namespace App\Services;

use App\Models\Tecnico;
use Illuminate\Support\Facades\Schema;

class TecnicoNomeResolver
{
    /**
     * Aliases confirmados do sistema antigo → nome canônico em `tecnicos`.
     *
     * @var array<string, string>
     */
    private const ALIASES_PARA_CANONICO = [
        'roberto kallyl' => 'Kallyl',
    ];

    /** @var array<string, array{nome: string, regiao: string}> */
    private array $indicePorChave = [];

    public function __construct()
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        foreach (Tecnico::query()->get(['nome', 'username', 'regiao']) as $tecnico) {
            $canonico = trim((string) $tecnico->nome);
            if ($canonico === '') {
                continue;
            }

            $regiao = trim((string) ($tecnico->regiao ?? ''));
            $entrada = ['nome' => $canonico, 'regiao' => $regiao];

            foreach ([$tecnico->nome, $tecnico->username] as $alias) {
                $chave = $this->chave((string) $alias);
                if ($chave !== '') {
                    $this->indicePorChave[$chave] = $entrada;
                }
            }
        }

        $this->aplicarAliasesConfirmados();
    }

    public function resolver(?string $nome): ?array
    {
        $chave = $this->chave((string) $nome);
        if ($chave === '' || $this->ehSemTecnico($chave)) {
            return null;
        }

        return $this->indicePorChave[$chave] ?? null;
    }

    /** @return array{tecnico: string, tecnico_regiao: string, tecnico_identificado: bool, tecnico_original: ?string} */
    public function resolverOuOriginal(string $nome): array
    {
        $nome = trim($nome);
        if ($nome === '' || $this->ehSemTecnico($this->chave($nome))) {
            return [
                'tecnico' => 'Sem técnico',
                'tecnico_regiao' => '',
                'tecnico_identificado' => false,
                'tecnico_original' => null,
            ];
        }

        $resolvido = $this->resolver($nome);
        if ($resolvido !== null) {
            return [
                'tecnico' => $resolvido['nome'],
                'tecnico_regiao' => $resolvido['regiao'],
                'tecnico_identificado' => true,
                'tecnico_original' => $nome !== $resolvido['nome'] ? $nome : null,
            ];
        }

        return [
            'tecnico' => $nome,
            'tecnico_regiao' => '',
            'tecnico_identificado' => false,
            'tecnico_original' => $nome,
        ];
    }

    /** @return list<string> */
    public function nomesCadastrados(): array
    {
        $nomes = [];
        foreach ($this->indicePorChave as $entrada) {
            $nomes[$entrada['nome']] = true;
        }

        return array_keys($nomes);
    }

    private function chave(string $nome): string
    {
        $nome = mb_strtolower(trim($nome));
        $nome = preg_replace('/\s+/u', ' ', $nome) ?? $nome;

        return $nome;
    }

    private function ehSemTecnico(string $chave): bool
    {
        return in_array($chave, ['sem técnico', 'sem tecnico', '—', '-'], true);
    }

    private function aplicarAliasesConfirmados(): void
    {
        foreach (self::ALIASES_PARA_CANONICO as $alias => $canonicoNome) {
            $chaveAlias = $this->chave($alias);
            $chaveCanonico = $this->chave($canonicoNome);
            if ($chaveAlias === '' || ! isset($this->indicePorChave[$chaveCanonico])) {
                continue;
            }

            $this->indicePorChave[$chaveAlias] = $this->indicePorChave[$chaveCanonico];
        }
    }
}
