<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\ManutencaoCorretivaService;
use App\Services\OpTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManutencaoCorretivaController extends Controller
{
    public function __construct(
        private ManutencaoCorretivaService $manutencaoCorretivaService,
        private OpTaskService $opTaskService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $resultado = $this->manutencaoCorretivaService->getManutencoesCorretivas(
            (int) $request->query('limit', 10),
            (int) $request->query('offset', 0),
            $request->query('status'),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('taskCode'),
            $request->query('dataInicio'),
            $request->query('dataFim'),
        );

        return response()->json([
            'message' => 'Manutenções corretivas listadas com sucesso',
            'manutencaoCorretiva' => $resultado,
        ]);
    }

    public function show(OpTask $manutencao_corretiva): JsonResponse
    {
        if (! $manutencao_corretiva->isManutencaoCorretivaPai()) {
            return response()->json(['message' => 'Manutenção corretiva não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Manutenção corretiva encontrada com sucesso',
            'manutencaoCorretiva' => $this->manutencaoCorretivaService->normalizarParaExibicao($manutencao_corretiva),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $manutencao = $this->manutencaoCorretivaService->createManutencaoCorretiva($request->all());

        return response()->json([
            'message' => 'Manutenção corretiva criada com sucesso',
            'manutencaoCorretiva' => $this->manutencaoCorretivaService->normalizarParaExibicao($manutencao),
        ], 201);
    }

    public function update(Request $request, OpTask $manutencao_corretiva): JsonResponse
    {
        if (! $manutencao_corretiva->isManutencaoCorretivaPai()) {
            return response()->json(['message' => 'Manutenção corretiva não encontrada'], 404);
        }

        $resultado = $this->manutencaoCorretivaService->updateManutencaoCorretiva($manutencao_corretiva, $request->all());

        return response()->json([
            'message' => 'Manutenção corretiva atualizada com sucesso',
            'manutencaoCorretiva' => $this->manutencaoCorretivaService->normalizarParaExibicao($resultado),
        ]);
    }

    public function destroy(OpTask $manutencao_corretiva): JsonResponse
    {
        if (! $manutencao_corretiva->isManutencaoCorretivaPai()) {
            return response()->json(['message' => 'Manutenção corretiva não encontrada'], 404);
        }

        $this->manutencaoCorretivaService->deleteManutencaoCorretiva($manutencao_corretiva);

        return response()->json(['message' => 'Manutenção corretiva deletada com sucesso']);
    }

    public function buscarEndereco(Request $request): JsonResponse
    {
        $endereco = $this->manutencaoCorretivaService->buscarEndereco((string) $request->query('coordenada', ''));

        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ]);
    }

    public function listarOS(int $id): JsonResponse
    {
        $pai = OpTask::find($id);

        if (! $pai?->isManutencaoCorretivaPai()) {
            return response()->json(['message' => 'Manutenção corretiva não encontrada'], 404);
        }

        $os = $this->opTaskService->listarOsVinculadas($id);

        return response()->json(['os' => $os]);
    }
}
