<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\CorrecaoDeSinalService;
use App\Services\OpTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorrecaoDeSinalController extends Controller
{
    public function __construct(
        private CorrecaoDeSinalService $correcaoDeSinalService,
        private OpTaskService $opTaskService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $resultado = $this->correcaoDeSinalService->getCorrecoesDeSinal(
            (int) $request->query('limit', 10),
            (int) $request->query('offset', 0),
            $request->query('status'),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('busca', $request->query('taskCode')),
            $request->query('dataInicio'),
            $request->query('dataFim'),
        );

        return response()->json([
            'message' => 'Correções de sinal listadas com sucesso',
            'correcaoDeSinal' => $resultado,
        ]);
    }

    public function show(OpTask $correcao_sinal): JsonResponse
    {
        if (! $correcao_sinal->isCorrecaoSinalPai()) {
            return response()->json(['message' => 'Correção de sinal não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Correção de sinal encontrada com sucesso',
            'correcaoDeSinal' => $this->correcaoDeSinalService->normalizarParaExibicao($correcao_sinal),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $correcao = $this->correcaoDeSinalService->createCorrecaoDeSinal($request->all());

        return response()->json([
            'message' => 'Correção de sinal criada com sucesso',
            'correcaoDeSinal' => $this->correcaoDeSinalService->normalizarParaExibicao($correcao),
        ], 201);
    }

    public function storeFromCaixa(Request $request): JsonResponse
    {
        $request->validate([
            'nome_cliente' => ['required', 'string', 'max:255'],
            'caixa' => ['nullable', 'string', 'max:120'],
            'porta' => ['nullable'],
            'serial' => ['nullable', 'string', 'max:64'],
            'sinal_rx' => ['nullable', 'numeric'],
            'codigo_cliente' => ['nullable'],
            'id_cliente_servico' => ['nullable'],
            'regiao' => ['nullable', 'string', 'max:120'],
        ]);

        $correcao = $this->correcaoDeSinalService->createFromClienteCaixa($request->all());

        return response()->json([
            'message' => 'Tarefa de correção de sinal criada com sucesso',
            'correcaoDeSinal' => $this->correcaoDeSinalService->normalizarParaExibicao($correcao),
        ], 201);
    }

    public function update(Request $request, OpTask $correcao_sinal): JsonResponse
    {
        if (! $correcao_sinal->isCorrecaoSinalPai()) {
            return response()->json(['message' => 'Correção de sinal não encontrada'], 404);
        }

        $resultado = $this->correcaoDeSinalService->updateCorrecaoDeSinal($correcao_sinal, $request->all());

        return response()->json([
            'message' => 'Correção de sinal atualizada com sucesso',
            'correcaoDeSinal' => $this->correcaoDeSinalService->normalizarParaExibicao($resultado),
        ]);
    }

    public function destroy(OpTask $correcao_sinal): JsonResponse
    {
        if (! $correcao_sinal->isCorrecaoSinalPai()) {
            return response()->json(['message' => 'Correção de sinal não encontrada'], 404);
        }

        $this->correcaoDeSinalService->deleteCorrecaoDeSinal($correcao_sinal);

        return response()->json(['message' => 'Correção de sinal deletada com sucesso']);
    }

    public function buscarEndereco(Request $request): JsonResponse
    {
        $endereco = $this->correcaoDeSinalService->buscarEndereco((string) $request->query('coordenada', ''));

        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ]);
    }

    public function listarOS(int $id): JsonResponse
    {
        $pai = OpTask::find($id);

        if (! $pai?->isCorrecaoSinalPai()) {
            return response()->json(['message' => 'Correção de sinal não encontrada'], 404);
        }

        $os = $this->opTaskService->listarOsVinculadas($id);

        return response()->json(['os' => $os]);
    }
}
