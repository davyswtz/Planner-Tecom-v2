<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Models\OsTecnico;
use Illuminate\Http\Request;

class PlannerChangesController extends Controller
{
    public function index(Request $request)
    {
        $categorias = $this->normalizarCategorias($request);

        if ($categorias === []) {
            return $this->respostaStats(['version' => '0', 'total' => 0], true);
        }

        return $this->respostaStats($this->obterStats($categorias), true);
    }

    /**
     * Long polling: mantém a requisição aberta até detectar mudança ou timeout.
     * Sem limites externos — usa só o seu MySQL e PHP.
     */
    public function wait(Request $request)
    {
        $categorias = $this->normalizarCategorias($request);
        $since = (string) $request->query('since', '');
        $timeout = min(28, max(5, (int) $request->query('timeout', 25)));
        $intervaloMs = 400;

        if ($categorias === []) {
            return $this->respostaStats(['version' => '0', 'total' => 0], true);
        }

        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $stats = $this->obterStats($categorias);
            $fingerprint = $this->fingerprint($stats);

            if ($since === '' || $fingerprint !== $since) {
                return $this->respostaStats($stats, $since === '' || $fingerprint !== $since);
            }

            usleep($intervaloMs * 1000);
        }

        $stats = $this->obterStats($categorias);

        return $this->respostaStats($stats, false);
    }

    private function normalizarCategorias(Request $request): array
    {
        return array_values(array_filter((array) $request->query('categorias', [])));
    }

    private function obterStats(array $categorias): array
    {
        $stats = $this->montarQuery($categorias)
            ->selectRaw('COUNT(*) as total, MAX(UNIX_TIMESTAMP(updated_at)) as last_ts')
            ->first();

        return [
            'version' => (string) (int) ($stats?->last_ts ?? 0),
            'total' => (int) ($stats?->total ?? 0),
        ];
    }

    private function fingerprint(array $stats): string
    {
        return ($stats['version'] ?? '0') . ':' . (int) ($stats['total'] ?? 0);
    }

    private function respostaStats(array $stats, bool $changed)
    {
        return response()->json([
            'version' => $stats['version'] ?? '0',
            'total' => (int) ($stats['total'] ?? 0),
            'fingerprint' => $this->fingerprint($stats),
            'changed' => $changed,
        ], 200);
    }

    private function montarQuery(array $categorias)
    {
        if (in_array('ordem-servico', $categorias, true)) {
            $taskIdsOsTecnicos = OsTecnico::query()
                ->whereNotNull('task_id')
                ->pluck('task_id');

            return OpTask::query()->where(function ($q) use ($taskIdsOsTecnicos) {
                $q->where('categoria', 'ordem-servico');
                if ($taskIdsOsTecnicos->isNotEmpty()) {
                    $q->orWhereIn('id', $taskIdsOsTecnicos);
                }
            });
        }

        $categoriasOtimizacao = [
            'otimizacao-rede',
            'otimizacao de rede',
            'otimização de rede',
            'OTIMIZACAO DE REDE',
            'OTIMIZAÇÃO DE REDE',
        ];

        $temOtimizacao = (bool) array_intersect($categorias, [
            'otimizacao-rede',
            'otimizacao de rede',
            'otimização de rede',
            'OTIMIZACAO DE REDE',
            'OTIMIZAÇÃO DE REDE',
        ]);

        return OpTask::query()
            ->whereNull('parent_task_id')
            ->where(function ($q) use ($categorias, $temOtimizacao) {
                $q->whereIn('categoria', $categorias);

                if ($temOtimizacao) {
                    $q->orWhere('taskCode', 'like', '%-OTM-%');
                }
            });
    }
}
