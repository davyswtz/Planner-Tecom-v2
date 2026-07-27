<?php

namespace App\Services;

use App\Models\OsTecnico;
use App\Models\Tecnico;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TecnicoService
{
    public function getTecnicos(?string $regiao = null): Collection
    {
        if (! Schema::hasTable('tecnicos')) {
            return collect();
        }

        return Tecnico::query()
            ->select('id', 'nome', 'regiao')
            ->when($regiao, fn ($query) => $query->whereIn('regiao', $this->regioesEquivalentes($regiao)))
            ->orderBy('nome')
            ->get()
            ->map(fn (Tecnico $tecnico) => [
                'id' => (int) $tecnico->id,
                'nome' => $tecnico->nome,
                'regiao' => $tecnico->regiao ?? '',
            ])
            ->values();
    }

    /**
     * Técnicos ativos no cadastro de usuários: linha em `tecnicos` com username
     * existente em `usuario` (mesma regra da tela Usuários → Téc).
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Tecnico>
     */
    public function queryCadastrados(?string $regiao = null)
    {
        if (! Schema::hasTable('tecnicos')) {
            return Tecnico::query()->whereRaw('0 = 1');
        }

        $query = Tecnico::query()
            ->whereNotNull('username')
            ->where('username', '!=', '');

        if (Schema::hasTable('usuario')) {
            $query->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('usuario')
                    ->whereColumn('usuario.username', 'tecnicos.username');
            });
        }

        return $query->when(
            $regiao,
            fn ($q) => $q->whereIn('regiao', $this->regioesEquivalentes($regiao))
        );
    }

    public function showTecnico(OsTecnico $tecnico): OsTecnico
    {
        return $tecnico;
    }

    /** @return list<string> */
    public function regioesEquivalentes(string $regiao): array
    {
        $mapa = [
            'Goval' => ['Goval', 'Governador Valadares'],
            'Governador Valadares' => ['Goval', 'Governador Valadares'],
            'Vale do Aço' => ['Vale do Aço', 'Caratinga'],
            'Caratinga' => ['Vale do Aço', 'Caratinga'], // legado → Vale do Aço
        ];

        return $mapa[$regiao] ?? [$regiao];
    }
}
