<?php

namespace App\Services;

use App\Models\AgendaOs;
use App\Models\OpTask;
use App\Models\OsTecnico;
use App\Models\Tecnico;
use App\Models\TecnicoIndisponibilidade;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgendaService
{
    public function programarAutomaticamente(OpTask $os, ?CarbonInterface $momento = null): ?AgendaOs
    {
        if ($os->categoria !== 'ordem-servico') {
            return null;
        }

        $responsaveis = OpTask::parseResponsaveis($os->responsavel);
        if ($responsaveis === [] || count($responsaveis) > 2) {
            return null;
        }

        $tecnicos = Tecnico::query()->whereIn('nome', $responsaveis)->get()->keyBy('nome');
        $espelhos = OsTecnico::query()
            ->where('task_id', $os->id)
            ->whereIn('tecnico_nome', $responsaveis)
            ->get()
            ->keyBy('tecnico_nome');

        if ($tecnicos->count() !== count($responsaveis) || $espelhos->count() !== count($responsaveis)) {
            return null;
        }

        $inicio = Carbon::instance(($momento ?? Carbon::now('America/Sao_Paulo'))->toDateTime())
            ->setTimezone('America/Sao_Paulo')
            ->setSecond(0);
        $inicio->setMinute($inicio->minute < 30 ? 0 : 30);

        return DB::transaction(function () use ($os, $espelhos, $tecnicos, $responsaveis, $inicio) {
            $os = OpTask::query()->lockForUpdate()->findOrFail($os->id);
            $espelhos = OsTecnico::query()->lockForUpdate()->whereIn('id', $espelhos->pluck('id'))->get()->keyBy('tecnico_nome');
            $tecnicos = Tecnico::query()->lockForUpdate()->whereIn('id', $tecnicos->pluck('id'))->get()->keyBy('nome');
            $programados = AgendaOs::query()->whereIn('os_tecnico_id', $espelhos->pluck('id'))->get();
            if ($programados->count() === count($responsaveis)) {
                return null;
            }

            if (TecnicoIndisponibilidade::query()
                ->whereIn('tecnico_id', $tecnicos->pluck('id'))
                ->whereDate('data_inicio', '<=', $inicio->toDateString())
                ->whereDate('data_fim', '>=', $inicio->toDateString())
                ->exists()) {
                return null;
            }

            while ($inicio->copy()->addHour()->lte($inicio->copy()->endOfDay()->setTime(23, 30))) {
                $fim = $inicio->copy()->addHour();
                $conflito = AgendaOs::query()
                    ->whereIn('tecnico_id', $tecnicos->pluck('id'))
                    ->whereDate('data', $inicio->toDateString())
                    ->where('hora_inicio', '<', $fim->format('H:i:s'))
                    ->where('hora_fim', '>', $inicio->format('H:i:s'))
                    ->exists();

                if (! $conflito) {
                    $criadas = collect();
                    foreach ($responsaveis as $responsavel) {
                        $existente = $programados->firstWhere('os_tecnico_id', $espelhos[$responsavel]->id);
                        $criadas->push($existente ?: AgendaOs::create([
                            'os_tecnico_id' => $espelhos[$responsavel]->id,
                            'tecnico_id' => $tecnicos[$responsavel]->id,
                            'data' => $inicio->toDateString(),
                            'hora_inicio' => $inicio->format('H:i:s'),
                            'hora_fim' => $fim->format('H:i:s'),
                        ]));
                    }

                    return $criadas->first();
                }

                $inicio->addMinutes(30);
            }

            return null;
        });
    }

    /** @param array<string, mixed> $dadosAgenda */
    public function atribuirTecnico(AgendaOs $agenda, Tecnico $tecnico, array $dadosAgenda = []): AgendaOs
    {
        return DB::transaction(function () use ($agenda, $tecnico, $dadosAgenda) {
            $agenda = AgendaOs::query()->lockForUpdate()->findOrFail($agenda->id);
            $osTecnico = OsTecnico::query()->lockForUpdate()->findOrFail($agenda->os_tecnico_id);
            $task = $osTecnico->task()->lockForUpdate()->firstOrFail();
            $outrosEspelhos = OsTecnico::query()
                ->where('task_id', $task->id)
                ->whereKeyNot($osTecnico->id)
                ->get();

            if ($outrosEspelhos->contains('tecnico_nome', $tecnico->nome)) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'Este técnico já está vinculado a esta OS.',
                ]);
            }

            $responsaveis = $outrosEspelhos->pluck('tecnico_nome')
                ->filter()
                ->push($tecnico->nome)
                ->values()
                ->all();
            $task->update(['responsavel' => OpTask::serializarResponsaveis($responsaveis)]);
            $osTecnico->update(['tecnico_nome' => $tecnico->nome, 'regiao' => $tecnico->regiao]);
            $agenda->update(array_merge($dadosAgenda, ['tecnico_id' => $tecnico->id]));

            if ($dadosAgenda !== []) {
                AgendaOs::query()
                    ->whereIn('os_tecnico_id', $outrosEspelhos->pluck('id'))
                    ->update($dadosAgenda);
            }

            return $agenda->fresh(['osTecnico.task', 'tecnico']);
        });
    }
}
