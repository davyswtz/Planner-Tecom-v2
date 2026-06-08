<?php

namespace App\Http\Controllers\Api;

use App\Models\OpTask;
use App\Services\TrocaDePosteService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrocaPosteController extends Controller
{
    public function __construct(private TrocaDePosteService $trocaDePosteService) {}

    public function index(Request $request)
    {
        $resultado = $this->trocaDePosteService->getTrocaDePoste(
            $request->query('limit', 10),
            $request->query('offset', 0),
            $request->query('status'),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('taskCode'),
            $request->query('dataInicio'),
            $request->query('dataFim')
        );

        return response()->json([
            'message' => 'Troca de poste listada com sucesso',
            'trocaDePoste' => $resultado
        ], 200);
    }

    public function show(OpTask $troca_poste)
    {
        if ($troca_poste->categoria !== 'troca-poste') {
            return response()->json(['message' => 'Troca de poste não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Troca de poste encontrada com sucesso',
            'trocaDePoste' => $troca_poste,
        ], 200);
    }


    public function store(Request $request)
    {
        $trocaDePoste = $this->trocaDePosteService->createTrocaDePoste($request->all());
        return response()->json([
            'message' => 'Troca de poste criada com sucesso',
            'trocaDePoste' => $trocaDePoste,
        ], 201);
    }

    public function update(Request $request, OpTask $troca_poste)
    {
        if ($troca_poste->categoria !== 'troca-poste') {
            return response()->json(['message' => 'Troca de poste não encontrada'], 404);
        }

        $resultado = $this->trocaDePosteService->updateTrocaDePoste($troca_poste, $request->all());
        return response()->json([
            'message' => 'Troca de poste atualizada com sucesso',
            'trocaDePoste' => $resultado,
        ], 200);
    }

    public function destroy(OpTask $troca_poste)
    {
        if ($troca_poste->categoria !== 'troca-poste') {
            return response()->json(['message' => 'Troca de poste não encontrada'], 404);
        }

        $this->trocaDePosteService->deleteTrocaDePoste($troca_poste);
        return response()->json(['message' => 'Troca de poste deletada com sucesso'], 200);
    }

    public function buscarEndereco(Request $request)
    {
        $coordenada = $request->query('coordenada');
        $endereco = $this->trocaDePosteService->buscarEndereco($coordenada);
        return response()->json([
            'message' => 'Endereço encontrado com sucesso',
            'endereco' => $endereco,
        ], 200);
    }

    public function listarOS($id)
    {
        $os = OpTask::where('parent_task_id', $id)
            ->where('categoria', 'ordem-servico')
            ->orderBy('criadaEm', 'desc')
            ->get();

        return response()->json(['os' => $os], 200);
    }
}