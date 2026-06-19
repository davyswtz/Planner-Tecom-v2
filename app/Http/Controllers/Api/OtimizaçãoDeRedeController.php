<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\OtimizaçãoDeRedeService;
use App\Services\OpTaskService;
use Illuminate\Http\Request;

class OtimizaçãoDeRedeController extends Controller
{
    public function __construct(
        private OtimizaçãoDeRedeService $otimizaçãoDeRedeService,
        private OpTaskService $opTaskService,
    ) {}

    public function index(Request $request)
    {
        $resultado = $this->otimizaçãoDeRedeService->getOtimizaçãoDeRede(
            $request->query('status'),
            (int) $request->query('limit', 10),
            (int) $request->query('offset', 0),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('taskCode'),
            $request->query('dataInicio'),
            $request->query('dataFim')
        );

        return response()->json([
            'message' => 'Otimização de rede listada com sucesso',
            'otimizacaoDeRede' => $resultado,
        ], 200);
    }

    public function show(OpTask $otimizacao_rede)
    {
        if ($otimizacao_rede->categoria !== 'otimizacao-rede') {
            return response()->json(['message' => 'Otimização de rede não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Otimização de rede encontrada com sucesso',
            'otimizacaoDeRede' => $otimizacao_rede,
        ], 200);
    }

    public function store(Request $request)
    {
        $otimizacaoDeRede = $this->otimizaçãoDeRedeService->createOtimizaçãoDeRede($request->all());

        return response()->json([
            'message' => 'Otimização de rede criada com sucesso',
            'otimizacaoDeRede' => $otimizacaoDeRede,
        ], 201);
    }

    public function update(Request $request, OpTask $otimizacao_rede)
    {
        if ($otimizacao_rede->categoria !== 'otimizacao-rede') {
            return response()->json(['message' => 'Otimização de rede não encontrada'], 404);
        }

        $resultado = $this->otimizaçãoDeRedeService->updateOtimizaçãoDeRede($otimizacao_rede, $request->all());

        return response()->json([
            'message' => 'Otimização de rede atualizada com sucesso',
            'otimizacaoDeRede' => $resultado,
        ], 200);
    }

    public function destroy(OpTask $otimizacao_rede)
    {
        if ($otimizacao_rede->categoria !== 'otimizacao-rede') {
            return response()->json(['message' => 'Otimização de rede não encontrada'], 404);
        }

        $this->otimizaçãoDeRedeService->deleteOtimizaçãoDeRede($otimizacao_rede);

        return response()->json(['message' => 'Otimização de rede deletada com sucesso'], 200);
    }

    public function buscarEndereco(Request $request)
    {
        $coordenada = $request->query('coordenada');
        $endereco = $this->otimizaçãoDeRedeService->buscarEndereco($coordenada);

        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ], 200);
    }

    public function listarOS($id)
    {
        $os = $this->opTaskService->listarOsVinculadas((int) $id);

        return response()->json(['os' => $os], 200);
    }
}
