<?php

namespace App\Services;

use App\Models\AgendaOs;
use App\Models\OpTask;
use App\Models\OsTecnico;
use App\Models\Tecnico;
use App\Models\TecnicoIndisponibilidade;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OsTecnicoService
{
    public function sincronizarParaOs(OpTask $os): void
    {
        if (($os->categoria ?? '') !== 'ordem-servico') {
            return;
        }

        $tecnicos = OpTask::parseResponsaveis($os->responsavel);
        if (count($tecnicos) > 2) {
            throw ValidationException::withMessages([
                'responsavel' => 'Uma OS pode possuir no máximo dois técnicos responsáveis.',
            ]);
        }

        $parent = $os->parent_task_id ? OpTask::find($os->parent_task_id) : null;
        $regiao = trim((string) $os->regiao) ?: trim((string) ($parent->regiao ?? ''));
        $existentes = OsTecnico::query()
            ->with('agenda.tecnico')
            ->lockForUpdate()
            ->where('task_id', $os->id)
            ->where(function ($query) {
                $query->where('correcao_dados', false)
                    ->orWhereNull('correcao_dados');
            })
            ->get();

        $agendados = $existentes->filter(fn (OsTecnico $registro) => $registro->agenda->isNotEmpty());
        if ($agendados->count() > 1 || ($agendados->isNotEmpty() && count($tecnicos) > 1)) {
            $this->sincronizarMultiplosAgendados($os, $existentes, $tecnicos, $regiao);
            $existentes = OsTecnico::query()
                ->with('agenda.tecnico')
                ->lockForUpdate()
                ->where('task_id', $os->id)
                ->where(function ($query) {
                    $query->where('correcao_dados', false)
                        ->orWhereNull('correcao_dados');
                })
                ->get();
            $agendados = collect();
        }

        if ($agendados->isNotEmpty()) {
            if (count($tecnicos) !== 1) {
                throw ValidationException::withMessages([
                    'responsavel' => 'Uma OS programada deve possuir exatamente um técnico responsável.',
                ]);
            }

            $novoTecnico = Tecnico::query()
                ->lockForUpdate()
                ->where('nome', $tecnicos[0])
                ->first();
            if (! $novoTecnico) {
                throw ValidationException::withMessages([
                    'responsavel' => 'O técnico selecionado não foi encontrado no cadastro.',
                ]);
            }
            if ($regiao !== '' && $novoTecnico->regiao !== $regiao) {
                throw ValidationException::withMessages([
                    'responsavel' => 'O técnico selecionado pertence a outra região.',
                ]);
            }

            foreach ($agendados as $registro) {
                foreach ($registro->agenda as $programacaoCarregada) {
                    $programacao = AgendaOs::query()->lockForUpdate()->findOrFail($programacaoCarregada->id);
                    $data = $programacao->data->toDateString();

                    $indisponivel = TecnicoIndisponibilidade::query()
                        ->where('tecnico_id', $novoTecnico->id)
                        ->whereDate('data_inicio', '<=', $data)
                        ->whereDate('data_fim', '>=', $data)
                        ->exists();
                    if ($indisponivel) {
                        throw ValidationException::withMessages([
                            'responsavel' => 'O técnico selecionado está indisponível na data programada.',
                        ]);
                    }

                    $conflito = AgendaOs::query()
                        ->where('tecnico_id', $novoTecnico->id)
                        ->whereDate('data', $data)
                        ->whereKeyNot($programacao->id)
                        ->where('hora_inicio', '<', $programacao->hora_fim)
                        ->where('hora_fim', '>', $programacao->hora_inicio)
                        ->exists();
                    if ($conflito) {
                        throw ValidationException::withMessages([
                            'responsavel' => 'O técnico selecionado já possui outra atividade nesse horário.',
                        ]);
                    }

                    $programacao->update(['tecnico_id' => $novoTecnico->id]);
                }

                $registro->update([
                    'tecnico_nome' => $novoTecnico->nome,
                    'regiao' => $novoTecnico->regiao,
                ]);
            }
        }

        $criadaEm = (string) ($os->criadaEm ?? now()->toIso8601String());
        $dataCriacao = substr($criadaEm, 0, 10);
        $dataConclusao = $this->statusEhConcluido((string) ($os->status ?? ''))
            ? substr((string) ($os->assinada_em ?? $dataCriacao), 0, 10)
            : '';

        $tecnicos = $tecnicos !== [] ? $tecnicos : [''];
        $dadosComuns = [
            'parent_task_id' => $os->parent_task_id,
            'ordem_servico' => $os->ordem_servico ?: ($os->numero_os ?? ''),
            'titulo' => $os->titulo ?? '',
            'task_code' => $os->taskCode ?? '',
            'categoria' => $os->categoria ?? 'ordem-servico',
            'regiao' => $regiao,
            'status' => $os->status ?? '',
            'prioridade' => $os->prioridade ?? 'Média',
            'data_criacao' => $dataCriacao !== '' ? $dataCriacao : null,
            'data_conclusao' => $dataConclusao,
            'criada_em' => $criadaEm,
            'correcao_dados' => false,
        ];

        foreach ($tecnicos as $tecnico) {
            $registro = $existentes->firstWhere('tecnico_nome', $tecnico);
            if ($registro) {
                $registro->update($dadosComuns);

                continue;
            }

            OsTecnico::create($dadosComuns + [
                'task_id' => $os->id,
                'tecnico_nome' => $tecnico,
            ]);
        }

        $existentes
            ->filter(fn (OsTecnico $registro) => $registro->agenda->isEmpty())
            ->reject(fn (OsTecnico $registro) => in_array($registro->tecnico_nome, $tecnicos, true))
            ->each->delete();
    }

    /**
     * @param  Collection<int, OsTecnico>  $existentes
     * @param  array<int, string>  $nomes
     */
    private function sincronizarMultiplosAgendados(
        OpTask $os,
        $existentes,
        array $nomes,
        string $regiao,
    ): void {
        $tecnicos = Tecnico::query()
            ->lockForUpdate()
            ->whereIn('nome', $nomes)
            ->get()
            ->keyBy('nome');

        if ($tecnicos->count() !== count($nomes)) {
            throw ValidationException::withMessages([
                'responsavel' => 'Um dos técnicos selecionados não foi encontrado no cadastro.',
            ]);
        }

        foreach ($tecnicos as $tecnico) {
            if ($regiao !== '' && $tecnico->regiao !== $regiao) {
                throw ValidationException::withMessages([
                    'responsavel' => 'Os técnicos selecionados devem pertencer à mesma região da OS.',
                ]);
            }
        }

        $referencia = $existentes->flatMap->agenda->sortBy('id')->first();
        $mantidos = $existentes->filter(
            fn (OsTecnico $registro) => in_array($registro->tecnico_nome, $nomes, true)
        );
        $substituiveis = $existentes->filter(
            fn (OsTecnico $registro) => $registro->agenda->isNotEmpty()
                && ! in_array($registro->tecnico_nome, $nomes, true)
        )->values();

        foreach ($nomes as $nome) {
            if ($mantidos->contains('tecnico_nome', $nome)) {
                continue;
            }

            $tecnico = $tecnicos[$nome];
            $registro = $substituiveis->shift();
            if (! $registro) {
                $modelo = $existentes->first();
                $registro = $modelo->replicate();
                $registro->task_id = $os->id;
                $registro->tecnico_nome = $nome;
                $registro->regiao = $tecnico->regiao;
                $registro->save();
                $existentes->push($registro->load('agenda'));
            }

            if ($referencia) {
                $data = $referencia->data->toDateString();
                $indisponivel = TecnicoIndisponibilidade::query()
                    ->where('tecnico_id', $tecnico->id)
                    ->whereDate('data_inicio', '<=', $data)
                    ->whereDate('data_fim', '>=', $data)
                    ->exists();
                $conflito = AgendaOs::query()
                    ->where('tecnico_id', $tecnico->id)
                    ->whereDate('data', $data)
                    ->whereNotIn('os_tecnico_id', $existentes->pluck('id'))
                    ->where('hora_inicio', '<', $referencia->hora_fim)
                    ->where('hora_fim', '>', $referencia->hora_inicio)
                    ->exists();

                if ($indisponivel || $conflito) {
                    throw ValidationException::withMessages([
                        'responsavel' => $indisponivel
                            ? 'Um dos técnicos selecionados está indisponível na data programada.'
                            : 'Um dos técnicos selecionados já possui outra atividade nesse horário.',
                    ]);
                }

                $programacao = $registro->agenda()->lockForUpdate()->first();
                if ($programacao) {
                    $programacao->update(['tecnico_id' => $tecnico->id]);
                } else {
                    AgendaOs::create([
                        'os_tecnico_id' => $registro->id,
                        'tecnico_id' => $tecnico->id,
                        'data' => $data,
                        'hora_inicio' => $referencia->hora_inicio,
                        'hora_fim' => $referencia->hora_fim,
                    ]);
                }
            }

            $registro->update([
                'tecnico_nome' => $nome,
                'regiao' => $tecnico->regiao,
            ]);
        }

        $existentes
            ->reject(fn (OsTecnico $registro) => in_array($registro->fresh()->tecnico_nome, $nomes, true))
            ->each->delete();
    }

    private function statusEhConcluido(string $status): bool
    {
        $normalizado = mb_strtolower(trim($status));

        return in_array($normalizado, [
            'finalizada',
            'finalizar',
            'concluída',
            'concluida',
            'fechada',
        ], true);
    }
}
