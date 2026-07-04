<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\OpTaskService;
use App\Services\TrocaDeEtiquetaService;
use Illuminate\Http\Request;

class TrocaEtiquetaController extends Controller
{
    public function __construct(
        private TrocaDeEtiquetaService $trocaDeEtiquetaService,
        private OpTaskService $opTaskService,
    ) {}

    public function index(Request $request)
    {
        $resultado = $this->trocaDeEtiquetaService->getTrocaDeEtiqueta(
            (int) $request->query('limit', 10),
            (int) $request->query('offset', 0),
            $request->query('status'),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('busca', $request->query('taskCode')),
            $request->query('dataInicio'),
            $request->query('dataFim')
        );

        return response()->json([
            'message' => 'Troca de etiqueta listada com sucesso',
            'trocaDeEtiqueta' => $resultado,
        ], 200);
    }

    public function show(OpTask $troca_etiqueta)
    {
        if (! $troca_etiqueta->isTarefaPaiOf('troca-etiqueta')) {
            return response()->json(['message' => 'Troca de etiqueta não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Troca de etiqueta encontrada com sucesso',
            'trocaDeEtiqueta' => $troca_etiqueta,
        ], 200);
    }

    public function store(Request $request)
    {
        $trocaDeEtiqueta = $this->trocaDeEtiquetaService->createTrocaDeEtiqueta($request->all());

        return response()->json([
            'message' => 'Troca de etiqueta criada com sucesso',
            'trocaDeEtiqueta' => $trocaDeEtiqueta,
        ], 201);
    }

    public function update(Request $request, OpTask $troca_etiqueta)
    {
        if (! $troca_etiqueta->isTarefaPaiOf('troca-etiqueta')) {
            return response()->json(['message' => 'Troca de etiqueta não encontrada'], 404);
        }

        $resultado = $this->trocaDeEtiquetaService->updateTrocaDeEtiqueta($troca_etiqueta, $request->all());

        return response()->json([
            'message' => 'Troca de etiqueta atualizada com sucesso',
            'trocaDeEtiqueta' => $resultado,
        ], 200);
    }

    public function destroy(OpTask $troca_etiqueta)
    {
        if (! $troca_etiqueta->isTarefaPaiOf('troca-etiqueta')) {
            return response()->json(['message' => 'Troca de etiqueta não encontrada'], 404);
        }

        $this->trocaDeEtiquetaService->deleteTrocaDeEtiqueta($troca_etiqueta);

        return response()->json(['message' => 'Troca de etiqueta deletada com sucesso'], 200);
    }

    public function buscarEndereco(Request $request)
    {
        $coordenada = $request->query('coordenada');
        $endereco = $this->trocaDeEtiquetaService->buscarEndereco($coordenada);

        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ], 200);
    }

    public function listarOS($id)
    {
        $pai = OpTask::find((int) $id);
        if (! $pai?->isTarefaPaiOf('troca-etiqueta')) {
            return response()->json(['message' => 'Troca de etiqueta não encontrada'], 404);
        }

        $os = $this->opTaskService->listarOsVinculadas((int) $id);

        return response()->json(['os' => $os], 200);
    }
}
