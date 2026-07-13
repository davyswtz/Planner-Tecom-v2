<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RompimentoService;
use App\Services\OpTaskService;
use App\Models\OpTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RompimentoController extends Controller
{
    public function __construct(
        private RompimentoService $rompimentoService,
        private OpTaskService $opTaskService,
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $limit = (int) $request->query('limit', 10);
        $offset = (int) $request->query('offset', 0);
        $regiao = $request->query('regiao');
        $tecnico = $request->query('tecnico');
        $taskCode = $request->query('busca', $request->query('taskCode'));
        $dataInicio = $request->query('dataInicio');
        $dataFim = $request->query('dataFim');

        $resultado = $this->rompimentoService->getRompimentos($status, $limit, $offset, $regiao, $tecnico, $taskCode, $dataInicio, $dataFim);
        return response()->json(['message' => 'Rompimentos listados com sucesso', 'rompimentos' => $resultado], 200);
    }
 
    public function show(OpTask $rompimento){
        if (! $rompimento->isRompimentoPai()) {
            return response()->json(['message' => 'Rompimento não encontrado'], 404);
        }

        return response()->json(['message' => 'Rompimento encontrado com sucesso', 'rompimento' => $rompimento], 200);
    }

    public function store(Request $request)
    {
       $rompimento = $this->rompimentoService->createRompimento($request->all());
       return response()->json(['message' => 'Rompimento criado com sucesso', 'rompimento' => $rompimento], 201);
    }

    public function update(Request $request, OpTask $rompimento){
        if (! $rompimento->isRompimentoPai()) {
            return response()->json(['message' => 'Rompimento não encontrado'], 404);
        }

        $resultado = $this->rompimentoService->updateRompimento($rompimento, $request->all());
        return response()->json(['message' => 'Rompimento atualizado com sucesso', 'rompimento' => $resultado], 200);
    }

    public function destroy(OpTask $rompimento){
        if (! $rompimento->isRompimentoPai()) {
            return response()->json(['message' => 'Rompimento não encontrado'], 404);
        }

        $this->rompimentoService->deleteRompimento($rompimento);
        return response()->json(['message' => 'Rompimento deletado com sucesso'], 200);
    }

    public function buscarEndereco(Request $request): JsonResponse
    {
        $endereco = $this->rompimentoService->buscarEndereco((string) $request->query('coordenada', ''));

        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ]);
    }

    public function listarOS($id)
{
    $pai = OpTask::find((int) $id);
    if (! $pai?->isRompimentoPai()) {
        return response()->json(['message' => 'Rompimento não encontrado'], 404);
    }

    $os = $this->opTaskService->listarOsVinculadas((int) $id);

    return response()->json(['os' => $os], 200);
}

public function updateOs(Request $request, OpTask $os){
    $resultado = $this->opTaskService->updateOpTask($os, $request->all());
    return response()->json(['message' => 'OS atualizada com sucesso', 'os' => $resultado], 200);
}
}
