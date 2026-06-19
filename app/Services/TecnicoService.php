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

    public function showTecnico(OsTecnico $tecnico): OsTecnico
    {
        return $tecnico;
    }

    /** @return list<string> */
    private function regioesEquivalentes(string $regiao): array
    {
        $mapa = [
            'Goval' => ['Goval', 'Governador Valadares'],
            'Vale do Aço' => ['Vale do Aço'],
            'Caratinga' => ['Caratinga'],
        ];

        return $mapa[$regiao] ?? [$regiao];
    }
}
