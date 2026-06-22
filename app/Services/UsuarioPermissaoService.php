<?php

namespace App\Services;

use App\Models\UsuarioPermissao;
use Illuminate\Support\Facades\Schema;

class UsuarioPermissaoService
{
    public function catalogo(): array
    {
        return collect(config('permissions', []))
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public function chavesValidas(): array
    {
        return array_keys(config('permissions', []));
    }

    public function possui(string $username, string $permissao): bool
    {
        $permissao = $this->normalizarChaveLegada($permissao);

        return in_array($permissao, $this->listarPorUsuario($username), true);
    }

    public function normalizarChaveLegada(string $permissao): string
    {
        return match ($permissao) {
            'conectar_webhook' => 'adicionar_webhook',
            default => $permissao,
        };
    }

    private function normalizarLista(array $permissoes): array
    {
        return collect($permissoes)
            ->map(fn (string $permissao) => $this->normalizarChaveLegada($permissao))
            ->filter(fn (string $permissao) => in_array($permissao, $this->chavesValidas(), true))
            ->unique()
            ->values()
            ->all();
    }

    public function listarPorUsuario(string $username): array
    {
        if (! Schema::hasTable('usuario_permissoes')) {
            return [];
        }

        return $this->normalizarLista(
            UsuarioPermissao::query()
                ->where('username', $username)
                ->orderBy('permissao')
                ->pluck('permissao')
                ->all()
        );
    }

    public function listarPorUsuarios(array $usernames): array
    {
        if (! Schema::hasTable('usuario_permissoes') || $usernames === []) {
            return [];
        }

        return UsuarioPermissao::query()
            ->whereIn('username', $usernames)
            ->orderBy('permissao')
            ->get()
            ->groupBy('username')
            ->map(fn ($rows) => $this->normalizarLista($rows->pluck('permissao')->all()))
            ->all();
    }

    public function sincronizar(string $username, ?array $permissoes): void
    {
        if (! Schema::hasTable('usuario_permissoes')) {
            return;
        }

        $permissoes = collect($permissoes ?? [])
            ->filter(fn ($permissao) => in_array($permissao, $this->chavesValidas(), true))
            ->unique()
            ->values();

        UsuarioPermissao::query()->where('username', $username)->delete();

        foreach ($permissoes as $permissao) {
            UsuarioPermissao::create([
                'username' => $username,
                'permissao' => $permissao,
            ]);
        }
    }
}
