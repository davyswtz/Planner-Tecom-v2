<?php

namespace App\Support;

use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UsuarioCargo
{
    private const PADRAO = [
        'desenvolvedor' => 'Desenvolvedor',
        'projetista_jr' => 'Projetista Jr',
        'projetista_pl' => 'Projetista Pl',
        'projetista_sr' => 'Projetista Sr',
        'supervisor' => 'Supervisor',
        'gestor' => 'Gestor',
    ];

    /** @return array<string, string> */
    public static function catalogo(): array
    {
        $cargos = config('usuario_cargos');

        if (is_array($cargos) && $cargos !== []) {
            return $cargos;
        }

        return self::PADRAO;
    }

    /** @return array<int, string> */
    public static function chavesValidas(): array
    {
        return array_keys(self::catalogo());
    }

    public static function label(?string $cargo): ?string
    {
        if (! $cargo) {
            return null;
        }

        return self::catalogo()[$cargo] ?? $cargo;
    }

    /** @return array<int, array{key: string, label: string}> */
    public static function catalogoFormatado(): array
    {
        return collect(self::catalogo())
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public static function dadosSessao(User $user): array
    {
        $ehTecnico = Schema::hasTable('tecnicos')
            && Tecnico::query()->where('username', $user->username)->exists();

        $cargo = $ehTecnico ? null : ($user->cargo ?? null);

        return [
            'id' => $user->username,
            'username' => $user->username,
            'funcao' => $ehTecnico ? 'tecnico' : 'projetista',
            'cargo' => $cargo,
            'cargo_label' => $ehTecnico ? null : self::label($cargo),
            'permissoes' => app(\App\Services\UsuarioPermissaoService::class)->listarPorUsuario($user->username),
        ];
    }
}
