<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OsTecnico;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TecnicoService
{
    public function getTecnicos(?string $regiao = null): Collection
    {
        $fromOs = DB::table('os_tecnicos')
            ->selectRaw('MIN(id) as id, tecnico_nome as nome, regiao')
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->groupBy('tecnico_nome', 'regiao')
            ->orderBy('nome')
            ->get();

        $fromTasks = OpTask::query()
            ->when($regiao, fn ($q) => $q->where('regiao', $regiao))
            ->whereNotNull('responsavel')
            ->where('responsavel', '!=', '')
            ->pluck('responsavel')
            ->flatMap(fn (string $nomes) => array_filter(array_map('trim', preg_split('/\s*,\s*/', $nomes))))
            ->unique()
            ->values();

        $merged = collect();
        $seen = [];

        if (Schema::hasTable('tecnicos')) {
            $fromCadastro = DB::table('tecnicos')
                ->select('id', 'nome', 'regiao')
                ->when($regiao, fn ($q) => $q->where(function ($sub) use ($regiao) {
                    $sub->where('regiao', $regiao)->orWhere('regiao', '');
                }))
                ->orderBy('nome')
                ->get();

            foreach ($fromCadastro as $row) {
                $key = mb_strtolower($row->nome) . '|' . mb_strtolower($row->regiao ?? '');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $merged->push([
                    'id' => (int) $row->id,
                    'nome' => $row->nome,
                    'regiao' => $row->regiao,
                ]);
            }
        }

        foreach ($fromOs as $row) {
            $key = mb_strtolower($row->nome) . '|' . mb_strtolower($row->regiao ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged->push([
                'id' => (int) $row->id,
                'nome' => $row->nome,
                'regiao' => $row->regiao,
            ]);
        }

        $syntheticId = 1_000_000;
        foreach ($fromTasks as $nome) {
            $key = mb_strtolower($nome) . '|' . mb_strtolower($regiao ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged->push([
                'id' => $syntheticId++,
                'nome' => $nome,
                'regiao' => $regiao ?? '',
            ]);
        }

        return $merged->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function showTecnico(OsTecnico $tecnico): OsTecnico
    {
        return $tecnico;
    }
}
