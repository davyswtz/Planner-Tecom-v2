<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgendaOs;
use App\Models\OsTecnico;
use App\Models\Tecnico;
use App\Models\TecnicoIndisponibilidade;
use App\Services\AgendaService;
use App\Services\TecnicoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AgendaController extends Controller
{
    public function __construct(
        private AgendaService $agendaService,
        private TecnicoService $tecnicoService,
    ) {}

    public function ordensDisponiveis(Request $request): JsonResponse
    {
        if (! Schema::hasTable('os_tecnicos')) {
            return response()->json(['ordens' => []]);
        }

        $dados = $request->validate([
            'busca' => ['nullable', 'string', 'max:120'],
            'regiao' => ['nullable', 'string', 'max:64'],
            'ordem' => ['nullable', 'in:recentes,antigas'],
        ]);

        $busca = trim((string) ($dados['busca'] ?? ''));
        $ordem = $dados['ordem'] ?? 'recentes';
        $regioes = isset($dados['regiao']) && $dados['regiao'] !== ''
            ? $this->tecnicoService->regioesEquivalentes($dados['regiao'])
            : null;

        $ordens = OsTecnico::query()
            ->with('task:id,taskCode,titulo,parent_task_id,ordem_servico,numero_os,regiao,status')
            ->when(
                Schema::hasTable('agenda_os'),
                fn ($query) => $query->whereDoesntHave('agenda'),
            )
            ->when($regioes, fn ($query) => $query->whereIn('regiao', $regioes))
            ->when($busca !== '', function ($query) use ($busca) {
                $like = '%'.addcslashes($busca, '%_\\').'%';
                $query->where(fn ($sub) => $sub
                    ->where('ordem_servico', 'like', $like)
                    ->orWhere('task_code', 'like', $like)
                    ->orWhere('titulo', 'like', $like));
            })
            ->when(
                $ordem === 'antigas',
                fn ($query) => $query->orderBy('id'),
                fn ($query) => $query->orderByDesc('id'),
            )
            ->limit(50)
            ->get(['id', 'task_id', 'ordem_servico', 'task_code', 'titulo', 'regiao', 'status']);

        return response()->json(['ordens' => $ordens]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agenda_os')) {
            throw ValidationException::withMessages([
                'os_tecnico_id' => 'Tabela da agenda ainda não foi criada. Rode as migrations no servidor.',
            ]);
        }

        $dados = $request->validate([
            'os_tecnico_id' => ['required', 'integer', 'exists:os_tecnicos,id'],
            'tecnico_id' => ['required', 'integer', 'exists:tecnicos,id'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);

        $inicio = Carbon::createFromFormat('H:i', $dados['hora_inicio']);
        $fim = Carbon::createFromFormat('H:i', $dados['hora_fim']);

        $agenda = DB::transaction(function () use ($dados, $inicio, $fim) {
            $osTecnico = OsTecnico::query()->lockForUpdate()->findOrFail($dados['os_tecnico_id']);
            $osTecnico->task()->lockForUpdate()->firstOrFail();
            $tecnico = Tecnico::query()->lockForUpdate()->findOrFail($dados['tecnico_id']);
            if ($this->tecnicoIndisponivel($tecnico->id, $dados['data'])) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'O técnico está indisponível nesta data.',
                ]);
            }

            $jaProgramada = AgendaOs::query()
                ->where('os_tecnico_id', $osTecnico->id)
                ->exists();
            if ($jaProgramada) {
                throw ValidationException::withMessages([
                    'os_tecnico_id' => 'Esta OS já possui uma programação na agenda.',
                ]);
            }

            $erro = $this->validarPeriodo(
                new AgendaOs,
                $tecnico->id,
                $dados['data'],
                $inicio,
                $fim,
            );
            if ($erro) {
                throw ValidationException::withMessages(['hora_inicio' => $erro]);
            }

            $agenda = AgendaOs::create(array_merge($dados, [
                'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fim' => $fim->format('H:i:s'),
            ]));

            return $this->agendaService->atribuirTecnico($agenda, $tecnico, [
                'data' => $dados['data'],
                'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fim' => $fim->format('H:i:s'),
            ]);
        });

        return response()->json($agenda->load(['osTecnico', 'tecnico']), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'data' => ['nullable', 'date_format:Y-m-d'],
            'regiao' => ['nullable', 'in:Vale do Aço,Goval'],
            'visao' => ['nullable', 'in:diaria,semanal'],
            'tecnico_id' => ['nullable', 'integer'],
        ]);

        $data = Carbon::createFromFormat('Y-m-d', $dados['data'] ?? now()->toDateString())->startOfDay();
        $regiao = $dados['regiao'] ?? 'Vale do Aço';
        $visao = $dados['visao'] ?? 'diaria';
        $inicio = $visao === 'semanal' ? $data->copy()->startOfWeek() : $data;
        $fim = $visao === 'semanal' ? $inicio->copy()->addDays(6) : $data;

        $tecnicosRegiao = $this->tecnicoService->queryCadastrados($regiao)
            ->when(
                Schema::hasTable('tecnico_indisponibilidades'),
                fn ($query) => $query->with(['indisponibilidades' => fn ($sub) => $sub
                    ->whereDate('data_inicio', '<=', $fim->toDateString())
                    ->whereDate('data_fim', '>=', $inicio->toDateString())
                    ->orderBy('data_inicio')]),
            )
            ->orderBy('nome')
            ->get(['id', 'nome', 'regiao', 'username']);

        $tecnicoId = $visao === 'semanal'
            ? ($tecnicosRegiao->firstWhere('id', (int) ($dados['tecnico_id'] ?? 0))?->id ?? $tecnicosRegiao->first()?->id)
            : null;

        $agenda = collect();
        if (Schema::hasTable('agenda_os')) {
            $agenda = AgendaOs::query()
                ->with(['osTecnico:id,ordem_servico,titulo,status,regiao', 'tecnico:id,nome,regiao'])
                ->whereDate('data', '>=', $inicio->toDateString())
                ->whereDate('data', '<=', $fim->toDateString())
                ->when($tecnicoId, fn ($query) => $query->where('tecnico_id', $tecnicoId))
                ->when(! $tecnicoId, fn ($query) => $query->whereIn('tecnico_id', $tecnicosRegiao->pluck('id')->pad(1, 0)))
                ->orderBy('data')->orderBy('hora_inicio')->get();
        }

        $tecnicos = $tecnicosRegiao;

        return response()->json(compact('agenda', 'tecnicos', 'regiao', 'visao', 'tecnicoId') + [
            'tecnicos_regiao' => $tecnicosRegiao,
            'data' => $data->toDateString(),
            'inicio_semana' => $inicio->toDateString(),
            'fim_semana' => $fim->toDateString(),
        ]);
    }

    public function mover(Request $request, AgendaOs $agendaOs): JsonResponse
    {
        $dados = $request->validate([
            'tecnico_id' => ['required', 'integer', 'exists:tecnicos,id'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
        ]);
        $agendaOs = DB::transaction(function () use ($agendaOs, $dados) {
            $agendaOs = AgendaOs::query()->lockForUpdate()->findOrFail($agendaOs->id);
            $tecnico = Tecnico::query()->lockForUpdate()->findOrFail($dados['tecnico_id']);
            if ($this->tecnicoIndisponivel($tecnico->id, $dados['data'])) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'O técnico está indisponível nesta data.',
                ]);
            }
            $inicioAtual = Carbon::parse($agendaOs->hora_inicio);
            $duracao = $inicioAtual->diffInMinutes(Carbon::parse($agendaOs->hora_fim));
            $inicio = Carbon::createFromFormat('H:i', $dados['hora_inicio']);
            $fim = $inicio->copy()->addMinutes($duracao);
            $programacoesRelacionadas = AgendaOs::query()
                ->whereIn('os_tecnico_id', OsTecnico::query()
                    ->where('task_id', $agendaOs->osTecnico->task_id)
                    ->select('id'))
                ->whereKeyNot($agendaOs->id)
                ->get();
            foreach ($programacoesRelacionadas as $relacionada) {
                if ($this->tecnicoIndisponivel($relacionada->tecnico_id, $dados['data'])) {
                    throw ValidationException::withMessages([
                        'tecnico_id' => 'Um dos técnicos da OS está indisponível nesta data.',
                    ]);
                }
                $erroRelacionado = $this->validarPeriodo(
                    $relacionada,
                    $relacionada->tecnico_id,
                    $dados['data'],
                    $inicio,
                    $fim,
                );
                if ($erroRelacionado) {
                    throw ValidationException::withMessages(['hora_inicio' => $erroRelacionado]);
                }
            }
            $erro = $this->validarPeriodo($agendaOs, $tecnico->id, $dados['data'], $inicio, $fim);
            if ($erro) {
                throw ValidationException::withMessages(['hora_inicio' => $erro]);
            }

            return $this->agendaService->atribuirTecnico(
                $agendaOs,
                $tecnico,
                [
                    'data' => $dados['data'],
                    'hora_inicio' => $inicio->format('H:i:s'),
                    'hora_fim' => $fim->format('H:i:s'),
                ],
            );
        });

        return response()->json($agendaOs);
    }

    public function registrarIndisponibilidade(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tecnico_indisponibilidades')) {
            throw ValidationException::withMessages([
                'tecnico_id' => 'Tabela de indisponibilidades ainda não foi criada. Rode as migrations.',
            ]);
        }

        $dados = $request->validate([
            'tecnico_id' => ['required', 'integer', 'exists:tecnicos,id'],
            'motivo' => ['required', 'in:ferias,atestado,folga,outro'],
            'data_inicio' => ['required', 'date_format:Y-m-d'],
            'data_fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ]);

        $sobreposto = TecnicoIndisponibilidade::query()
            ->where('tecnico_id', $dados['tecnico_id'])
            ->whereDate('data_inicio', '<=', $dados['data_fim'])
            ->whereDate('data_fim', '>=', $dados['data_inicio'])
            ->exists();
        if ($sobreposto) {
            throw ValidationException::withMessages([
                'data_inicio' => 'Este técnico já possui uma indisponibilidade nesse período.',
            ]);
        }

        $indisponibilidade = TecnicoIndisponibilidade::create($dados);
        $conflitos = Schema::hasTable('agenda_os')
            ? AgendaOs::query()
                ->where('tecnico_id', $dados['tecnico_id'])
                ->whereBetween('data', [$dados['data_inicio'], $dados['data_fim']])
                ->count()
            : 0;

        return response()->json([
            'indisponibilidade' => $indisponibilidade,
            'agendamentos_no_periodo' => $conflitos,
        ], 201);
    }

    public function removerIndisponibilidade(TecnicoIndisponibilidade $indisponibilidade): JsonResponse
    {
        $indisponibilidade->delete();

        return response()->json(status: 204);
    }

    public function atualizarDuracao(Request $request, AgendaOs $agendaOs): JsonResponse
    {
        $dados = $request->validate(['hora_fim' => ['required', 'date_format:H:i']]);
        $agendaOs = DB::transaction(function () use ($agendaOs, $dados) {
            $agendaOs = AgendaOs::query()->lockForUpdate()->findOrFail($agendaOs->id);
            Tecnico::query()->lockForUpdate()->findOrFail($agendaOs->tecnico_id);
            $inicio = Carbon::parse($agendaOs->hora_inicio);
            $fim = Carbon::createFromFormat('H:i', $dados['hora_fim']);
            $erro = $this->validarPeriodo(
                $agendaOs,
                $agendaOs->tecnico_id,
                $agendaOs->data->toDateString(),
                $inicio,
                $fim,
            );
            if ($erro) {
                throw ValidationException::withMessages(['hora_fim' => $erro]);
            }

            $agendaOs->update(['hora_fim' => $fim->format('H:i:s')]);
            $relacionadas = AgendaOs::query()
                ->whereIn('os_tecnico_id', OsTecnico::query()
                    ->where('task_id', $agendaOs->osTecnico->task_id)
                    ->select('id'))
                ->whereKeyNot($agendaOs->id)
                ->get();
            foreach ($relacionadas as $relacionada) {
                $erroRelacionado = $this->validarPeriodo(
                    $relacionada,
                    $relacionada->tecnico_id,
                    $relacionada->data->toDateString(),
                    Carbon::parse($relacionada->hora_inicio),
                    $fim,
                );
                if ($erroRelacionado) {
                    throw ValidationException::withMessages(['hora_fim' => $erroRelacionado]);
                }
                $relacionada->update(['hora_fim' => $fim->format('H:i:s')]);
            }

            return $agendaOs->fresh(['osTecnico', 'tecnico']);
        });

        return response()->json($agendaOs);
    }

    private function validarPeriodo(AgendaOs $item, int $tecnicoId, string $data, Carbon $inicio, Carbon $fim): ?string
    {
        if ($inicio->minute % 30 !== 0 || $fim->minute % 30 !== 0 || $fim->lte($inicio) || $inicio->diffInMinutes($fim) < 30 || $fim->gt(Carbon::createFromTime(23, 30))) {
            return 'Use intervalos de 30 minutos, com duração mínima de 30 minutos e término até 23:30.';
        }
        $conflito = AgendaOs::query()->where('tecnico_id', $tecnicoId)->whereDate('data', $data)
            ->whereKeyNot($item->getKey())->where('hora_inicio', '<', $fim->format('H:i:s'))
            ->where('hora_fim', '>', $inicio->format('H:i:s'))->exists();

        return $conflito ? 'Este período conflita com outra atividade do técnico.' : null;
    }

    private function tecnicoIndisponivel(int $tecnicoId, string $data): bool
    {
        if (! Schema::hasTable('tecnico_indisponibilidades')) {
            return false;
        }

        return TecnicoIndisponibilidade::query()
            ->where('tecnico_id', $tecnicoId)
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->exists();
    }
}
