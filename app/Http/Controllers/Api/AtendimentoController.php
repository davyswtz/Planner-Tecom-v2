<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\AtendimentoService;
use App\Services\OpTaskService;
use Illuminate\Http\Request;

class AtendimentoController extends Controller
{
    public function __construct(
        private AtendimentoService $atendimentoService,
        private OpTaskService $opTaskService,
    ) {}

    public function index(Request $request)
    {
        $resultado = $this->atendimentoService->getAtendimentos(
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
            'message' => 'Atendimentos listados com sucesso',
            'atendimentos' => $resultado,
        ], 200);
    }

    public function show(OpTask $atendimento)
    {
        if ($atendimento->categoria !== 'atendimento-cliente') {
            return response()->json(['message' => 'Atendimento não encontrado'], 404);
        }

        return response()->json([
            'message' => 'Atendimento encontrado com sucesso',
            'atendimento' => $this->atendimentoService->normalizarParaExibicao($atendimento),
        ], 200);
    }

    public function store(Request $request)
    {
        $atendimento = $this->atendimentoService->createAtendimento($request->all());

        return response()->json([
            'message' => 'Atendimento criado com sucesso',
            'atendimento' => $this->atendimentoService->normalizarParaExibicao($atendimento),
        ], 201);
    }

    public function update(Request $request, OpTask $atendimento)
    {
        if ($atendimento->categoria !== 'atendimento-cliente') {
            return response()->json(['message' => 'Atendimento não encontrado'], 404);
        }

        $resultado = $this->atendimentoService->updateAtendimento($atendimento, $request->all());

        return response()->json([
            'message' => 'Atendimento atualizado com sucesso',
            'atendimento' => $this->atendimentoService->normalizarParaExibicao($resultado),
        ], 200);
    }

    public function destroy(OpTask $atendimento)
    {
        if ($atendimento->categoria !== 'atendimento-cliente') {
            return response()->json(['message' => 'Atendimento não encontrado'], 404);
        }

        $this->atendimentoService->deleteAtendimento($atendimento);

        return response()->json(['message' => 'Atendimento deletado com sucesso'], 200);
    }

    public function buscarEndereco(Request $request)
    {
        $coordenada = $request->query('coordenada');
        $endereco = $this->atendimentoService->buscarEndereco($coordenada);

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
