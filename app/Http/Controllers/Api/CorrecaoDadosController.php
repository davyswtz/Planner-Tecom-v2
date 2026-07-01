<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CorrecaoDadosService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CorrecaoDadosController extends Controller
{
    public function __construct(private CorrecaoDadosService $correcaoDadosService) {}

    public function index()
    {
        return response()->json([
            'total' => $this->correcaoDadosService->listar()->count(),
            'items' => $this->correcaoDadosService->listar(),
            'categorias' => $this->correcaoDadosService->categoriasTela(),
        ], 200);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);

        try {
            $item = $this->correcaoDadosService->criar($dados);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Registro criado com sucesso.', 'item' => $item], 201);
    }

    public function update(Request $request, int $id)
    {
        $dados = $this->validar($request, true);

        try {
            $item = $this->correcaoDadosService->atualizar($id, $dados);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Registro atualizado com sucesso.', 'item' => $item], 200);
    }

    public function destroy(int $id)
    {
        try {
            $this->correcaoDadosService->excluir($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['message' => 'Registro excluído com sucesso.'], 200);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $regras = [
            'registro' => ($parcial ? 'sometimes|' : '').'required_without:tipo|in:tarefa,os',
            'tipo' => ($parcial ? 'sometimes|' : '').'required_without:registro|in:tarefa,os',
            'titulo' => ($parcial ? 'sometimes|' : '').'required|string|max:500',
            'categoria' => ($parcial ? 'sometimes|' : '').'required|string|max:48',
            'tecnico' => 'nullable|string|max:120',
            'regiao' => 'nullable|string|max:64',
            'status' => ($parcial ? 'sometimes|' : '').'required|string|max:48',
            'prioridade' => 'nullable|string|max:24',
            'data_criacao' => ($parcial ? 'sometimes|' : '').'required|date_format:Y-m-d',
            'data_conclusao' => 'nullable|date_format:Y-m-d',
            'descricao' => 'nullable|string',
        ];

        $dados = $request->validate($regras);
        $dados['registro'] = $dados['registro'] ?? $dados['tipo'] ?? null;

        return $dados;
    }
}
